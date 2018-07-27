<?php

namespace App\Queue\Producer;

use Interop\Amqp\AmqpContext;
use Psr\Container\ContainerInterface;

class CommonProducer
{
    /** @var AmqpContext */
    protected $context;

    public function __construct(ContainerInterface $container)
    {
        $this->context = $container->get('enqueue.transport.rabbitmq_amqp.context');
    }

    public function send(string $queue, array $message): void
    {
        $amqpQueue = $this->context->createQueue($queue);
        $this->context->declareQueue($amqpQueue);

        $amqpMessage = $this->context->createMessage(json_encode($message));
        $this->context->createProducer()->send($amqpQueue, $amqpMessage);
    }
}