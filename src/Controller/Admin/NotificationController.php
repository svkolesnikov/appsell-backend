<?php

namespace App\Controller\Admin;

use App\DCI\PushCreating;
use App\Entity\Offer;
use App\Entity\PushNotification;
use App\Entity\User;
use App\Exception\Admin\AdminException;
use App\Form\PushNotificationType;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use RedjanYm\FCMBundle\FCMClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/notifications", name="app_notification_list")
     *
     * @Security("has_role('ROLE_APP_NOTIFICATION_LIST')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \UnexpectedValueException
     */
    public function listAction(Request $request): Response
    {
        $filter     = new ParameterBag($request->get('filter', []));
        $page       = $request->get('_page', 1);
        $perPage    = $request->get('_per_page', 16);
        $offset     = ($page-1) * $perPage;
        $criteria   = [];
        $items      = [];

        if (!$this->isGranted('ROLE_ADMIN')) {
            $criteria['sender'] = $this->getUser();
        }

        if (!empty($filter->get('offer_id'))) {
            $criteria['offer'] = $filter->get('offer_id');
        }

        try {

            $items = $this->em
                ->getRepository(PushNotification::class)
                ->findBy($criteria, ['ctime' => 'DESC'], $perPage, $offset);

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось получить список' . $ex->getMessage());
        }

        return $this->render('pages/notification/list.html.twig', [
            'items'  => $items,
            'filter' => $filter->all(),
            'pager'  => [
                '_per_page' => $perPage,
                '_page'     => $page,
                '_has_more' => \count($items) >= $perPage
            ]
        ]);
    }

    /**
     * @Route("/admin/notifications/create", name="app_notification_create")
     *
     * @Security("has_role('ROLE_APP_NOTIFICATION_CREATE')")
     *
     * @param Request $request
     * @param PushCreating $pushCreating
     * @param UserGroupManager $userGroupManager
     * @return Response
     */
    public function createAction(Request $request, PushCreating $pushCreating, UserGroupManager $userGroupManager): Response
    {
        $form = $this->createForm(PushNotificationType::class, ['offer_id' => $request->get('offer_id')], [
            'user' => $this->getUser(),
            'is_seller' => $userGroupManager->hasGroup($this->getUser(), UserGroupEnum::SELLER())
        ]);

        $form->handleRequest($request);
        $data = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                // проверим наличие выбранных пользователей
                if (array_key_exists('users', $data)) {
                    $recipients = ($data['users'])->toArray();
                }

                // а затем выбранных групп
                elseif (array_key_exists('groups', $data)) {
                    $qb = $this->em->createQueryBuilder();
                    $recipients = $qb
                        ->select('u')
                        ->from(User::class, 'u')
                        ->innerJoin('u.groups', 'g')
                        ->where($qb->expr()->in('g.code', $data['groups']))
                        ->getQuery()
                        ->getResult();
                }

                // немного погруститм, т.к. почему-то ничего не выбрано
                else {
                    throw new AdminException('Не указаны получатели!');
                }

                // уведомление не обязательно привязывать к конкретному офферу
                $offer = null;
                if (!empty($data['offer_id'])) {
                    $offer = $this->em->getRepository(Offer::class)->find($data['offer_id']);

                    if (null === $offer) {
                        throw new AdminException('Указанный оффер не обнаружен!');
                    }
                }

                // попробуем отправить
                $pushCreating->send($data['message'], $this->getUser(), $recipients, $offer);

                $this->addFlash('success', 'Запись создана');

                return $this->redirectToRoute('app_notification_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при добавлении записи: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/notification/create.html.twig', [
            'form'   => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/notifications/{id}/remove", name="app_notification_remove")
     *
     * @Security("has_role('ROLE_APP_NOTIFICATION_DELETE')")
     *
     * @param PushNotification $notification
     * @return Response
     */
    public function removeAction(PushNotification $notification): Response
    {
        try {

            $this->em->remove($notification);
            $this->em->flush();

            $this->addFlash('success', 'Запись успешно удалена');

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Ошибка при удалении записи: ' . $ex->getMessage());
        }

        return $this->redirectToRoute('app_notification_list');
    }

    /**
     * @Route("/admin/notifications/{id}/show", name="app_notification_show")
     *
     * @Security("has_role('ROLE_APP_NOTIFICATION_LIST')")
     *
     * @param PushNotification $notification
     * @return Response
     */
    public function showAction(PushNotification $notification): Response
    {


        return $this->render('pages/notification/show.html.twig', ['item'  => $notification]);
    }
}