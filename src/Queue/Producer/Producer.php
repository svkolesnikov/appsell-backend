<?php

namespace App\Queue\Producer;

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Psr\Container\ContainerInterface;

class Producer
{
    /** @var AmqpQueue[] */
    protected $queueCache;

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

        if (!isset($this->queueCache[$queue])) {
            $amqpQueue = $this->context->createQueue($queue);
            $amqpQueue->addFlag(AmqpQueue::FLAG_DURABLE);
            $this->context->declareQueue($amqpQueue);

            $this->queueCache[$queue] = $amqpQueue;

        } else {
            $amqpQueue = $this->queueCache[$queue];
        }

        // Опубликуем сообщение
        $amqpMessage = $this->context->createMessage(json_encode($message));
        $amqpMessage->setDeliveryMode(AmqpMessage::DELIVERY_MODE_PERSISTENT);

        $this->context->createProducer()->send($amqpQueue, $amqpMessage);
    }
}