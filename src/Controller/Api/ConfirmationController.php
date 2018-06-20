<?php

namespace App\Controller\Api;

use App\Exception\Api\AuthException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\NotificationTypeEnum;
use App\Notification\Producer\SystemProducer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\NotFoundResponse;
use App\Swagger\Annotations\TokenParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints;
use App\Entity;

/**
 * @Route("/confirmations")
 */
class ConfirmationController
{
    use FormTrait;

    /**
     * @SWG\Post(
     *
     *  path = "/confirmations/email",
     *  summary = "Подтверждение email кодом",
     *  description = "",
     *  tags = { "Confirmations" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "code" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "code", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 202,
     *      description = "Email подтвержден"
     *  ),
     *
     *  @BadRequestResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route("/email", methods = { "POST" })
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SystemProducer $producer
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \App\Exception\Api\FormValidationException
     * @throws AuthException
     */
    public function confirmEmailAction(Request $request, EntityManagerInterface $entityManager, SystemProducer $producer): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('email', Type\TextType::class, ['constraints' => [new Constraints\Email(), new Constraints\NotBlank()]])
            ->add('code',  Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        /** @var Entity\UserConfirmation $confirmation */
        $confirmation = $entityManager->getRepository('App:UserConfirmation')->findOneBy(['email' => $data['email']]);
        if (null === $confirmation) {
            throw new NotFoundHttpException('Не найден email для подтверждения');
        }

        // Если email уже подтвержден
        // то говорим что все хорошо

        if ($confirmation->getEmailConfirmed()) {
            return new JsonResponse(null, JsonResponse::HTTP_ACCEPTED);
        }

        if ($confirmation->getEmailConfirmationCode() !== $data['code']) {
            throw new AuthException('Неверный код подтверждения');
        }

        // В случае успешного подтверждения email
        // активируем пользователя

        $user = $confirmation->getUser();
        $user->setEmail($confirmation->getEmail());
        $user->setActive(true);

        // Запишем данные, когда email был подтвержден

        $confirmation->setEmailConfirmationTime(new \DateTime());
        $confirmation->setEmailConfirmed(true);
        $confirmation->setEmailConfirmationCode(null);

        $entityManager->persist($user);
        $entityManager->flush();

        // Отправим уведомление о регистрации сотрудника

        $employer = $user->getProfile()->getEmployer();

        $producer->produce(NotificationTypeEnum::NEW_EMPLOYEE(), [
            'subject' => 'Зарегистрировался новый сотрудник продавца',
            'email'   => $data['email'],
            'company' => $employer ? $employer->getProfile()->getCompanyTitle() : null
        ]);

        return new JsonResponse(null, JsonResponse::HTTP_ACCEPTED);
    }
}