<?php

namespace App\Queue\Processor;


use App\Queue\MessageHandler\ExampleHandler;
use App\Queue\MessageHandler\HandlerInterface;
use App\Queue\MessageHandler\MainHandler;
use App\Queue\MessageHandler\ReportHandler;
use Enqueue\Consumption\QueueSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class Processor implements PsrProcessor, QueueSubscriberInterface
{
    public const QUEUE_REPORT = 'report';
    public const QUEUE_EXAMPLE = 'example';
    public const QUEUE_MAIN = 'main';

    /** @var HandlerInterface[] */
    protected $handlers;

    /** @var LoggerInterface */
    protected $logger;

    public static function getSubscribedQueues(): array
    {
        return [
            self::QUEUE_REPORT,
            self::QUEUE_EXAMPLE,
            self::QUEUE_MAIN
        ];
    }

    public function __construct(
        LoggerInterface $logger,
        MainHandler $main,
        ReportHandler $report,
        ExampleHandler $example
    ) {
        $this->logger = $logger;
        $this->handlers = [
            self::QUEUE_REPORT  => $report,
            self::QUEUE_MAIN    => $main,
            self::QUEUE_EXAMPLE => $example
        ];
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $data  = json_decode($message->getBody(), true) ?? [];
        $queue = $data['queue'] ?? null;

        // Обработаем сообщение соответствующим обработчиком
        // Собственно, обработчики задаются в конструкторе

        if (null !== $queue && isset($this->handlers[$queue])) {
            return $this->handlers[$queue]->handle($data);
        }

        $this->logger->error('Отсутствует обработчик для сообщения', $data);
        return self::ACK;
    }
}