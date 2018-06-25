<?php

namespace App\Controller\Admin;

use App\Entity\EventType;
use App\Form\EventType as EventFormType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EventTypeController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/event-types", name="app_event_type_list")
     *
     * @Security("has_role('ROLE_APP_EVENT_TYPE_LIST')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \UnexpectedValueException
     */
    public function listAction(Request $request): Response
    {
        $page       = $request->get('_page', 1);
        $perPage    = $request->get('_per_page', 16);
        $offset     = ($page-1) * $perPage;

        try {
            $items = $this->em->getRepository(EventType::class)->findBy([], [], $perPage, $offset);
        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось получить список событий. ' . $ex->getMessage());
        }

        return $this->render('pages/event_type/list.html.twig', [
            'types' => $items,
            'pager' => [
                '_per_page' => $perPage,
                '_page'     => $page,
                '_has_more' => count($items) >= $perPage
            ]
        ]);
    }

    /**
     * @Route("/admin/event-types/{id}/edit", name="app_event_type_edit")
     *
     * @Security("has_role('ROLE_APP_EVENT_TYPE_EDIT')")
     *
     * @param Request $request
     *
     * @param EventType $eventType
     * @return Response
     */
    public function editAction(Request $request, EventType $eventType): Response
    {
        $form = $this->createForm(EventFormType::class, $eventType);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($eventType);
                $this->em->flush();

                $this->addFlash('success', 'Запись обновлена');

                return $this->redirectToRoute('app_event_type_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при обновлении записи: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/event_type/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'edit'
        ]);
    }

    /**
     * @Route("/admin/event-types/create", name="app_event_type_create")
     *
     * @Security("has_role('ROLE_APP_EVENT_TYPE_CREATE')")
     *
     * @param Request $request
     * @return Response
     * @throws \LogicException
     */
    public function createAction(Request $request): Response
    {
        $eventType  = new EventType();
        $form       = $this->createForm(EventFormType::class, $eventType);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($eventType);
                $this->em->flush();

                $this->addFlash('success', 'Запись создана');

                return $this->redirectToRoute('app_event_type_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при добавлении записи: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/event_type/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'create'
        ]);
    }

    /**
     * @Route("/admin/event-types/{id}/remove", name="app_event_type_remove")
     *
     * @Security("has_role('ROLE_APP_EVENT_TYPE_DELETE')")
     *
     * @param EventType $eventType
     * @return Response
     */
    public function removeAction(EventType $eventType): Response
    {
        try {

            $this->em->remove($eventType);
            $this->em->flush();

            $this->addFlash('success', 'Запись успешно удалена');

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Ошибка при удалении записи: ' . $ex->getMessage());
        }

        return $this->redirectToRoute('app_event_type_list');
    }
}