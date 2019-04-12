<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessQueueCommand extends Command
{
    use LockableTrait;

    protected function configure()
    {
        $this
            ->setName('app:queue:process')
            ->setDescription('Обработка сообщений из очередей (или одной очереди)')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Наименование очереди')
            ->addOption('message-limit', null, InputOption::VALUE_OPTIONAL, 'Обработка N сообщений и выход')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getOption('queue');
        $pid   = $this->getName() . $queue;

        if (!$this->lock($pid)) {
            $output->writeln('В данный момент работает другой экземпляр команды.');

            return 0;
        }

        $commandName = 'enqueue:transport:consume';
        $command     = $this->getApplication()->find($commandName);

        $arguments = [
            'command'           => $commandName,
            'processor-service' => 'queue.processor'
        ];

        if (!empty($queue)) {
            $arguments['--queue'] = [$queue];
        }

        $messageLimit = (int) $input->getOption('message-limit');
        if ($messageLimit) {
            $arguments['--message-limit'] = $messageLimit;
        }

        $command->run(new ArrayInput($arguments), $output);

        $this->release();

        return 1;
    }
}