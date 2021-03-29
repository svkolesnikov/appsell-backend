<?php

namespace App\Controller\Admin;

use App\Entity\PromoCode;
use App\Exception\Admin\LoadExternalImageException;
use App\Form\PromoCodeType;
use App\Security\UserGroupManager;
use App\Service\ImageService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PromoCodeController extends BaseController
{
    /** @var EntityManager */
    protected $em;

    /** @var  UserGroupManager */
    protected $userGroupManager;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        UserGroupManager $userGroupManager,
        LoggerInterface $l
    )
    {
        $this->em = $em;
        $this->userGroupManager = $userGroupManager;
        $this->logger = $l;
    }

    /**
     * @Route("/admin/promo-coddes", name="app_promo_code_list")
     *
     * @Security("is_granted('ROLE_APP_PROMO_CODE_LIST')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    public function listAction(Request $request): Response
    {
        $page            = $request->get('_page', 1);
        $perPage         = $request->get('_per_page', 16);
        $offset          = ($page-1) * $perPage;
        $criteria        = [];

        $items = $this->em->getRepository(PromoCode::class)->findBy($criteria, [], $perPage, $offset);


        return $this->render('pages/promo-code/list.html.twig', [
            'items'            => $items,
            'pager'            => [
                '_per_page'    => $perPage,
                '_page'        => $page,
                '_has_more'    => \count($items) >= $perPage
            ]
        ]);
    }



    /**
     * @Route("/admin/promo-codes/create", name="app_promo_code_create")
     *
     * @Security("is_granted('ROLE_APP_PROMO_CODE_CREATE')")
     *
     * @param Request $request
     * @param ImageService $imageService
     * @return Response
     */
    public function createAction(Request $request, ImageService $imageService): Response
    {
        $promoCode = new PromoCode();

        $promoCode->setStatus(PromoCode::STATUS_FRESH);

        $form = $this->createForm(PromoCodeType::class, $promoCode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($promoCode);

                $this->em->flush();

                $this->addFlash('success', 'Запись создана');

                return $this->redirectToRoute('app_promo_code_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при добавлении записи: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/promo-code/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'create'
        ]);
    }
    
    /**
     * @Route("/admin/promo-codes/{id}/edit", name="app_promo_code_edit")
     *
     * @Security("is_granted('ROLE_APP_PROMO_CODE_EDIT')")
     *
     * @param Request $request
     *
     * @param PromoCode $promoCode
     * @param ImageService $imageService
     * @return Response
     */
    public function editAction(Request $request, PromoCode $promoCode, ImageService $imageService): Response
    {
        $form = $this->createForm(PromoCodeType::class, $promoCode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($promoCode);

                $this->em->flush();

                $this->addFlash('success', 'Запись обновлена');

                return $this->redirectToRoute('app_promo_code_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при обновлении записи: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/promo-code/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'edit'
        ]);
    }

    /**
     * @Route("/admin/promo-codes/{id}/delete", name="app_promo_code_delete")
     *
     * @Security("is_granted('ROLE_APP_PROMO_CODE_DELETE')")
     *
     * @param PromoCode $promoCode
     * @return Response
     */
    public function deleteAction(PromoCode $promoCode): Response
    {
        try {
            $this->em->remove($promoCode);
            $this->em->flush();

            $this->addFlash('success', 'Запись успешно удалена');

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось удалить запись. ' . $ex->getMessage());
        }

        return $this->redirectToRoute('app_promo_code_list');
    }
}