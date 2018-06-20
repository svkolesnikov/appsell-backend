<?php

namespace App\Notification\Notificator;

use App\Exception\Api\NotificationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Templating\EngineInterface;

class EmailNotificator implements Notificator
{
    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var EngineInterface */
    protected $templating;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(\Swift_Mailer $mailer, EngineInterface $templating, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->logger = $logger;
    }

    /**
     * @param string $template
     * @param array $params
     * @throws NotificationException
     */
    public function send(string $template, array $params): void
    {
        try {

            $subject = $params['subject'] ?? null;
            $to      = $params['to'] ?? null;
            $body    = $this->templating->render("notifications/email/${template}.txt.twig", $params);
            $message = (new \Swift_Message($subject, $body))->setTo($to);

            $this->mailer->send($message);

        } catch (\RuntimeException $ex) {
            throw new NotificationException('Не удалось отправить email уведомление', $ex);
        }
    }
}