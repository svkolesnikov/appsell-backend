<?php

namespace App\Queue\Producer;

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Psr\Container\ContainerInterface;

class Producer
{
    /** @var AmqpContext */
    protected $context;

    public function __construct(ContainerInterface $container)
    {
        $this->context = $container->get('enqueue.transport.rabbitmq_amqp.context');
    }

    public function send(string $queue, array $message): void
    {
        // По этому полю будет определяться обработчик на выходе
        $message['queue'] = $queue;

        // Получим очередь
        $amqpQueue = $this->context->createQueue($queue);
        $amqpQueue->addFlag(AmqpQueue::FLAG_DURABLE);
        $this->context->declareQueue($amqpQueue);

        // Опубликуем сообщение
        $amqpMessage = $this->context->createMessage(json_encode($message));
        $amqpMessage->setDeliveryMode(AmqpMessage::DELIVERY_MODE_PERSISTENT);
        $this->context->createProducer()->send($amqpQueue, $amqpMessage);
    }
}