<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BeforeActionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [['parseJsonBody', 0]]
        ];
    }

    /**
     * @param ControllerEvent $event
     * @throws \LogicException
     */
    public function parseJsonBody(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getContentType() === 'json') {
            $request->request->add(json_decode($request->getContent(), true) ?? []);
        }
    }
}