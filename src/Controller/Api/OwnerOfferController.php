<?php

namespace App\Controller\Api;

use App\DataSource\OwnerOfferDataSource;
use App\Entity\Compensation;
use App\Entity\Offer;
use App\Entity\OfferLink;
use App\Entity\User;
use App\Exception\Api\ApiException;
use App\Exception\Api\FormValidationException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\CompensationTypeEnum;
use App\Lib\Enum\CurrencyEnum;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Lib\Enum\OfferTypeEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\SummaryOfferSchema;
use App\Swagger\Annotations\OfferStatisticSchema;
use App\Swagger\Annotations\OfferSchema;
use App\Swagger\Annotations\BadRequestResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;

/**
 * @Route("/owners")
 */
class OwnerOfferController
{
    use FormTrait;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var UserGroupManager */
    protected $groupManager;

    public function __construct(TokenStorageInterface $ts, EntityManagerInterface $em, UserGroupManager $gm)
    {
        $this->tokenStorage = $ts;
        $this->entityManager = $em;
        $this->groupManager = $gm;
    }

    /**
     * @SWG\Get(
     *
     *  path = "/owners/current/offers",
     *  summary = "Офферы заказчика",
     *  description = "",
     *  tags = { "Owners" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "type", in = "query", type = "string", description = "app или service"),
     *  @SWG\Parameter(name = "limit", default = 20, in = "query", type = "integer"),
     *  @SWG\Parameter(name = "offset", default = 0, in = "query", type = "integer"),
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "Список получен",
     *      @SWG\Schema(
     *          type = "array",
     *          items = @SummaryOfferSchema()
     *      )
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse()
     * )
     *
     * @Route("/current/offers", methods = { "GET" })
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws FormValidationException
     */
    public function getOffersAction(Request $request): JsonResponse
    {
        try {

            /** @var User $user */
            $user     = $this->tokenStorage->getToken()->getUser();
            $limit    = (int)$request->get('limit', 20);
            $offset   = (int)$request->get('offset', 0);

            $criteria = ['owner' => $user, 'is_deleted' => false];

            if (null !== $request->get('type')) {
                $criteria['type'] = new OfferTypeEnum($request->get('type'));
            }

            if (!$this->groupManager->hasGroup($user, UserGroupEnum::OWNER())) {
                throw new AccessDeniedHttpException('Owners only access');
            }

            $offers = $this->entityManager
                ->getRepository(Offer::class)
                ->findBy($criteria, ['mtime' => 'DESC'], $limit, $offset);

            return new JsonResponse(array_values(array_map(function(Offer $offer) {

                $compensation = array_map(function(Compensation $c) {
                    return [
                        'id' => $c->getId(),
                        'type' => $c->getType(),
                        'price' => $c->getPrice(),
                        'currency' => $c->getCurrency(),
                        'description' => $c->getDescription(),
                    ];
                }, $offer->getCompensations()->toArray());

                $links = array_map(function(OfferLink $l) {
                    return [
                        'id' => $l->getId(),
                        'type' => $l->getType(),
                        'url' => $l->getUrl()
                    ];
                }, $offer->getLinks()->toArray());

                $arr = [
                    'id' => $offer->getId(),
                    'type' => $offer->getType(),
                    'is_active' => $offer->isActive(),
                    'title' => $offer->getTitle(),
                    'description' => $offer->getDescription(),
                    'active_from' => $offer->getActiveFrom()->format('d-m-Y H:i:s'),
                    'active_to' => $offer->getActiveTo()->format('d-m-Y H:i:s'),
                    'ctime' => $offer->getCtime()->format('d-m-Y H:i:s'),
                    'mtime' => $offer->getMtime()->format('d-m-Y H:i:s'),
                    'compensations' => $compensation,
                    'links' => $links
                ];

                return $arr;

            }, $offers)));

        } catch (\UnexpectedValueException $ex) {
            throw new FormValidationException(
                'Передан неверный параметр',
                ['type' => 'Допустимые значения: ' . implode(', ', OfferTypeEnum::toArray())]
            );
        }
    }

    /**
     * @SWG\Post(
     *
     *  path = "/owners/offers",
     *  summary = "Создание нового оффера",
     *  description = "",
     *  tags = { "Owners" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body", @OfferSchema()),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Оффер создан"
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse()
     * )
     *
     * @Route("/offers", methods = { "POST" })
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws FormValidationException
     * @throws ApiException
     */
    public function createAction(Request $request): JsonResponse
    {
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$this->groupManager->hasGroup($user, UserGroupEnum::OWNER())) {
            throw new AccessDeniedHttpException('Owners only access');
        }

        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('type',          Type\ChoiceType::class,     ['constraints' => [new Constraints\NotBlank()], 'choices' => OfferTypeEnum::toArray()])
            ->add('title',         Type\TextType::class,       ['constraints' => [new Constraints\NotBlank()]])
            ->add('description',   Type\TextType::class,       [])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        try {

            $offer = new Offer();
            $offer
                ->setType(new OfferTypeEnum($data['type']))
                ->setTitle($data['title'])
                ->setDescription($data['description'])
                ->setOwner($user);

            $this->entityManager->persist($offer);
            $this->entityManager->flush();

        } catch (\Exception $ex) {
            throw new ApiException('Не удалось добавить оффер', $ex);
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    /**
     * @SWG\Put(
     *
     *  path = "/owners/offers/{id}",
     *  summary = "Редактирование своего оффера",
     *  description = "",
     *  tags = { "Owners" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "id", in = "path", type = "string", required=true),
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body", @OfferSchema()),
     *
     *  @SWG\Response(
     *      response = 204,
     *      description = "Оффер отредактирован"
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse()
     * )
     *
     * @Route("/offers/{id}", methods = { "PUT" })
     * @param Request $request
     * @return JsonResponse
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws FormValidationException
     * @throws ApiException
     */
    public function editAction(Request $request): JsonResponse
    {
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$this->groupManager->hasGroup($user, UserGroupEnum::OWNER())) {
            throw new AccessDeniedHttpException('Owners only access');
        }

        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('id',            Type\TextType::class,       [])
            ->add('type',          Type\ChoiceType::class,     ['constraints' => [new Constraints\NotBlank()], 'choices' => OfferTypeEnum::toArray()])
            ->add('title',         Type\TextType::class,       ['constraints' => [new Constraints\NotBlank()]])
            ->add('description',   Type\TextType::class,       [])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        $offerId = $request->get('id');

            /** @var Offer $offer */
            $offer = $this->entityManager->find('App:Offer', $offerId);

            if (null === $offer) {
                throw new NotFoundHttpException(sprintf('Оффер %s не найден', $offerId));
            }

            if ($offer->getOwner() !== $user) {
                throw new AccessDeniedHttpException(sprintf('Оффер %s не принадлежит пользователю', $offerId));
            }

        try {

            $offer
                ->setType(new OfferTypeEnum($data['type']))
                ->setTitle($data['title'])
                ->setDescription($data['description']);

            $this->entityManager->persist($offer);
            $this->entityManager->flush();

        } catch (\Exception $ex) {
            throw new ApiException('Не удалось обновить оффер', $ex);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @SWG\Get(
     *
     *  path = "/owners/offers/statistic",
     *  summary = "Статистика по офферам",
     *  description = "",
     *  tags = { "Owners" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "status", in = "query", type = "string", description="processing/complete/rejected", required=true),
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "Список получен",
     *      @SWG\Schema(
     *          type = "array",
     *          items = @OfferStatisticSchema()
     *      )
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse()
     * )
     *
     * @Route("/offers/statistic", methods = { "GET" })
     * @param Request $request
     * @param UserGroupManager $groupManager
     * @return JsonResponse
     * @throws FormValidationException
     * @throws \App\Exception\Api\DataSourceException
     */
    public function getStatisticAction(Request $request, UserGroupManager $groupManager, OwnerOfferDataSource $dataSource): JsonResponse
    {
        try {

            /** @var User $user */
            $user   = $this->tokenStorage->getToken()->getUser();
            $status = new OfferExecutionStatusEnum($request->get('status'));

            if (!$groupManager->hasGroup($user, UserGroupEnum::OWNER())) {
                throw new AccessDeniedHttpException('Owner only access');
            }

            return new JsonResponse($dataSource->getExecutionStatistic($user, $status));

        } catch (\UnexpectedValueException $ex) {
            throw new FormValidationException(
                'Передан неверный параметр',
                ['status' => 'Допустимые значения: ' . implode(', ', OfferExecutionStatusEnum::toArray())]
            );
        }
    }
}