<?php

namespace App\Controller\Api;

use App\Entity\Compensation;
use App\Entity\Offer;
use App\Exception\Api\ApiException;
use App\Exception\Api\FormValidationException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\CompensationTypeEnum;
use App\Lib\Enum\CurrencyEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\OfferCompensationSchema;
use App\Swagger\Annotations\BadRequestResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;

/**
 * @Route("/owners/offers/{offer_id}")
 */
class OwnerOfferCompensationController
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
     * @SWG\Post(
     *
     *  path = "/owners/offers/{offer_id}/compensations",
     *  summary = "Создание новой ссылки",
     *  description = "",
     *  tags = { "Owners" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "offer_id", in = "path", type = "string"),
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body", @OfferCompensationSchema()),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Компенсация создана"
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse()
     * )
     *
     * @Route("/compensations", methods = { "POST" })
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
            ->add('type',        Type\ChoiceType::class, ['constraints' => [new Constraints\NotBlank()], 'choices' => CompensationTypeEnum::toArray()])
            ->add('description', Type\TextType::class,   ['constraints' => [new Constraints\NotBlank()]])
            ->add('currency',    Type\ChoiceType::class, ['constraints' => [new Constraints\NotBlank()], 'choices' => CurrencyEnum::toArray()])
            ->add('price',       Type\TextType::class,   ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        $offerId = $request->get('offer_id');

        /** @var Offer $offer */
        $offer = $this->entityManager->find('App:Offer', $offerId);

        if (null === $offer) {
            throw new NotFoundHttpException(sprintf('Оффер %s не найден', $offerId));
        }

        if ($offer->getOwner() !== $user) {
            throw new AccessDeniedHttpException(sprintf('Оффер %s не принадлежит пользователю', $offerId));
        }

        try {

            $compensation = new Compensation();
            $compensation
                ->setType(new CompensationTypeEnum($data['type']))
                ->setCurrency(new CurrencyEnum($data['currency']))
                ->setDescription($data['description'])
                ->setPrice($data['price']);

            $offer->addCompensation($compensation);

            $this->entityManager->persist($offer);
            $this->entityManager->flush();

        } catch (\Exception $ex) {
            throw new ApiException('Не удалось создать новую компенсацию', $ex);
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    /**
     * @SWG\Put(
     *
     *  path = "/owners/offers/{offer_id}/compensations/{id}",
     *  summary = "Редактирование компенсации",
     *  description = "",
     *  tags = { "Owners" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "offer_id", in = "path", type = "string"),
     *  @SWG\Parameter(name = "id", in = "path", type = "string"),
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body", @OfferCompensationSchema()),
     *
     *  @SWG\Response(
     *      response = 204,
     *      description = "Компенсация отредактирована"
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse()
     * )
     *
     * @Route("/compensations/{id}", methods = { "PUT" })
     * @param Request $request
     * @return JsonResponse
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
            ->add('type',        Type\ChoiceType::class, ['constraints' => [new Constraints\NotBlank()], 'choices' => CompensationTypeEnum::toArray()])
            ->add('description', Type\TextType::class,   ['constraints' => [new Constraints\NotBlank()]])
            ->add('currency',    Type\ChoiceType::class, ['constraints' => [new Constraints\NotBlank()], 'choices' => CurrencyEnum::toArray()])
            ->add('price',       Type\TextType::class,   ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        $offerId = $request->get('offer_id');

        /** @var Offer $offer */
        $offer = $this->entityManager->find('App:Offer', $offerId);
        if (null === $offer) {
            throw new NotFoundHttpException(sprintf('Оффер %s не найден', $offerId));
        }

        if ($offer->getOwner() !== $user) {
            throw new AccessDeniedHttpException(sprintf('Оффер %s не принадлежит пользователю', $offerId));
        }

        /** @var Compensation $compensation */
        $compensation = $this->entityManager
            ->getRepository('App:Compensation')
            ->findOneBy([
                'id' => $request->get('id'),
                'offer' => $offer
            ]);

        if (null === $compensation) {
            throw new NotFoundHttpException(sprintf('Компенсация %s не найдена', $request->get('id')));
        }

        try {

            $compensation
                ->setType(new CompensationTypeEnum($data['type']))
                ->setCurrency(new CurrencyEnum($data['currency']))
                ->setDescription($data['description'])
                ->setPrice($data['price']);

            $this->entityManager->persist($compensation);
            $this->entityManager->flush();

        } catch (\Exception $ex) {
            throw new ApiException('Не удалось обновить новую компенсацию', $ex);
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    /**
     * @SWG\Delete(
     *
     *  path = "/owners/offers/{offer_id}/compensations/{id}",
     *  summary = "Удаление компенсации",
     *  description = "",
     *  tags = { "Owners" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "offer_id", in = "path", type = "string"),
     *  @SWG\Parameter(name = "id", in = "path", type = "string"),
     *
     *  @SWG\Response(
     *      response = 204,
     *      description = "Компенсация удалена"
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse()
     * )
     *
     * @Route("/compensations/{id}", methods = { "DELETE" })
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws ApiException
     */
    public function deleteAction(Request $request): JsonResponse
    {
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$this->groupManager->hasGroup($user, UserGroupEnum::OWNER())) {
            throw new AccessDeniedHttpException('Owners only access');
        }

        $offerId = $request->get('offer_id');

        /** @var Offer $offer */
        $offer = $this->entityManager->find('App:Offer', $offerId);
        if (null === $offer) {
            throw new NotFoundHttpException(sprintf('Оффер %s не найден', $offerId));
        }

        if ($offer->getOwner() !== $user) {
            throw new AccessDeniedHttpException(sprintf('Оффер %s не принадлежит пользователю', $offerId));
        }

        /** @var Compensation $compensation */
        $compensation = $this->entityManager
            ->getRepository('App:Compensation')
            ->findOneBy([
                'id' => $request->get('id'),
                'offer' => $offer
            ]);

        if (null === $compensation) {
            throw new NotFoundHttpException(sprintf('Компенсация %s не найдена', $request->get('id')));
        }

        try {

            $this->entityManager->remove($compensation);
            $this->entityManager->flush();

        } catch (\Exception $ex) {
            throw new ApiException('Не удалось удалить компенсацию', $ex);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}