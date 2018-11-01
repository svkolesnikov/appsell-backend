<?php

namespace App\Controller\Api;

use App\DCI\ActionLogging;
use App\Entity\Repository\OfferExecutionRepository;
use App\Exception\Api\ApiException;
use App\Exception\Api\AuthException;
use App\Exception\AppException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\ActionLogItemTypeEnum;
use App\Lib\Enum\PayoutDestinationEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use App\SolarStaff\Client;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\SolarStaffInfoSchema;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\PayoutInfoSchema;
use App\Swagger\Annotations\PayoutResultSchema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints;
use App\Entity;

class SolarStaffController
{
    use FormTrait;

    /** @var Client */
    protected $client;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ActionLogging */
    protected $actionLogging;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(Client $ssc, EntityManagerInterface $em, ActionLogging $al, TokenStorageInterface $ts)
    {
        $this->client = $ssc;
        $this->entityManager = $em;
        $this->actionLogging = $al;
        $this->tokenStorage = $ts;
    }

    /**
     * @SWG\Get(
     *
     *  path = "/solar-staff",
     *  summary = "Информация о Solar Staff",
     *  description = "",
     *  tags = { "Info", "Solar-Staff" },
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "",
     *      @SolarStaffInfoSchema()
     *  )
     * )
     *
     * @Route("/solar-staff", methods = { "GET" })
     */
    public function indexAction(): JsonResponse
    {
        return new JsonResponse([
            'oferta_url' => $this->client->getOfertaUrl(),
            'login_url'  => $this->client->getLoginUrl()
        ]);
    }

    /**
     * @SWG\Post(
     *
     *  path = "/solar-staff/registration/employees",
     *  summary = "Регистрация сотрудника Solar Staff",
     *  description = "",
     *  tags = { "Solar-Staff", "Registration" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "password" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "password", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Пользователь зарегистрирован"
     *  ),
     *
     *  @BadRequestResponse()
     * )
     *
     * @Route("/solar-staff/registration/employees", methods = { "POST" })
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @param UserGroupManager $groupManager
     * @return JsonResponse
     * @throws AuthException
     * @throws \App\Exception\Api\FormValidationException
     */
    public function registerEmployeeAction(Request $request, UserPasswordEncoderInterface $encoder, UserGroupManager $groupManager): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('email',    Type\TextType::class, ['constraints' => [new Constraints\Email(), new Constraints\NotBlank()]])
            ->add('password', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        /** @var Entity\User $employer */
        $employer = $this->entityManager->getRepository('App:User')->findOneBy(['id' => $this->client->getEmployerId()]);
        if (null === $employer) {
            throw new AuthException('Неверный идентификатор компании Solar Staff');
        }

        try {
            $this->entityManager->beginTransaction();

            // Создадим пользователя

            $user = new Entity\User();
            $user->setEmail($data['email']);
            $user->setPassword($encoder->encodePassword($user, $data['password']));
            $user->setActive(true);

            // Сделаем пользователя "сотрудником"

            try {
                $groupManager->addGroup($user, UserGroupEnum::EMPLOYEE());
            } catch (AppException $ex) {
                throw new ApiException($ex->getMessage(), $ex);
            }

            // Подтвердим email пользователя

            $confirmation = $user->getConfirmation();
            $confirmation->setEmail($user->getEmail());
            $confirmation->setEmailConfirmationTime(new \DateTime());
            $confirmation->setEmailConfirmed(true);

            // Привяжем к компании Solar-Staff

            $profile = $user->getProfile();
            $profile->setEmployer($employer);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // После успешного сохранения зарегистрируем в Solar-Staff
            // и запишем ID сотрудника из SS в профиль

            $profile->setSolarStaffId($this->client->createWorker($data['email'], $data['password']));

            $this->entityManager->persist($profile);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (UniqueConstraintViolationException $ex) {

            $this->entityManager->rollback();
            throw new AuthException('Email already exists', $ex);

        } catch (\Exception $ex) {

            // Если что не так пошло - откатываем транзакцию
            // пользователь у нас не создастся

            $this->entityManager->rollback();

            // Залогируем попытку регистрации

            $this->actionLogging->log(
                ActionLogItemTypeEnum::SOLAR_STAFF_REGISTRATION(),
                'Неудачная попытка регистрации: ' . $ex->getMessage(),
                ['email' => $data['email']],
                $request
            );

            throw $ex;
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    /**
     * @SWG\Get(
     *
     *  path = "/solar-staff/payout",
     *  summary = "Получение информации о доступном для вывода остатке средств",
     *  description = "",
     *  tags = { "Solar-Staff" },
     *
     *  @TokenParameter(),
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "Информация получена",
     *      @PayoutInfoSchema()
     *  )
     * )
     *
     * @Route("/solar-staff/payout", methods = { "GET" })
     *
     * @return JsonResponse
     */
    public function payoutInfoAction(): JsonResponse
    {
        /** @var OfferExecutionRepository $repository */
        $repository = $this->entityManager->getRepository('App:OfferExecution');
        $amount = $repository->getPayoutAvailableAmount($this->tokenStorage->getToken()->getUser());

        return new JsonResponse(['amount' => $amount]);
    }

    /**
     * @SWG\Post(
     *
     *  path = "/solar-staff/payout",
     *  summary = "Вывод средств на внутренний счет пользователя в Solar Staff",
     *  description = "",
     *  tags = { "Solar-Staff" },
     *
     *  @TokenParameter(),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Вывод средств завершен",
     *      @PayoutResultSchema()
     *  ),
     *
     *  @BadRequestResponse(),
     *  @AccessDeniedResponse()
     * )
     *
     * @Route("/solar-staff/payout", methods = { "POST" })
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function doPayoutAction(Request $request): JsonResponse
    {
        /** @var OfferExecutionRepository $repository */
        $repository = $this->entityManager->getRepository('App:OfferExecution');

        /** @var Entity\User $employee */
        $employee = $this->tokenStorage->getToken()->getUser();

        if (null === $employee->getProfile()->getSolarStaffId()) {
            throw new AccessDeniedHttpException('Вывод средств доступен только после регистрации через SolarStaff');
        }

        $executions = $repository->getPayoutAvailable($employee);
        $amount     = $repository->getAmountFor($executions);

        if ($amount < 1) {
            throw new BadRequestHttpException('Недостаточно средств для вывода');
        }

        // Сформируем список выполненных приложений, для отчетночти
        // в solar staff

        $attributes = [];
        $offers     = [];

        foreach ($executions as $e) {

            $offerTitle = $e->getOffer()->getTitle();
            if (!isset($offers[$offerTitle])) {
                $offers[$offerTitle] = 0;
            }

            $offers[$offerTitle]++;
        }

        // Должно получиться что-то вроде:
        //
        // Яндекс еда - 7;
        // Телеграмм - 52;
        // eBox - 21

        foreach ($offers as $title => $count) {
            $attributes[] = sprintf('%s – %d', $title, $count);
        }

        // Поехали выводить бабло

        $this->entityManager->beginTransaction();

        try {

            // Сформируем вывод средств в ЛК solar staff

            $response = $this->client->payout($employee->getProfile()->getSolarStaffId(), $amount, $attributes);

            // Сохраним информацию о транзакции к себе в БД

            $transaction = new Entity\PayoutTransaction();
            $transaction->setReceiver($employee);
            $transaction->setAmount($amount);
            $transaction->setDestination(PayoutDestinationEnum::SOLAR_STAFF());
            $transaction->setInfo([

                // Информация о клиенте
                'request' => array_intersect_key(
                    $request->server->all(),
                    array_flip([
                        'HTTP_USER_AGENT',
                        'REMOTE_ADDR'
                    ])
                ),

                // Информация об ответе от SS
                'response' => $response
            ]);

            $this->entityManager->persist($transaction);

            foreach ($executions as $e) {
                $e->setPayoutTransaction($transaction);
                $this->entityManager->persist($e);
            }

            // Готово

            $this->entityManager->flush();
            $this->entityManager->commit();

            return new JsonResponse([
                'amount' => $amount,
                'redirect_url' => $this->client->getLoginUrl()
            ], JsonResponse::HTTP_CREATED);

        } catch (\Exception $ex) {

            // Если что не так пошло - откатываем транзакцию
            // пользователь у нас не создастся

            $this->entityManager->rollback();

            // Залогируем попытку регистрации

            $this->actionLogging->log(
                ActionLogItemTypeEnum::SOLAR_STAFF_PAYOUT(),
                'Неудачная попытка вывода средств: ' . $ex->getMessage(),
                ['email' => $employee->getEmail()],
                $request
            );

            throw $ex;
        }
    }
}