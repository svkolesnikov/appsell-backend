<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SberbankCommand extends Command
{

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure()
    {
        $this
		->setName('sberbank:deamon')
		->setDescription("Запускает роуты '/payments/status', '/payments/autoRevoke' и '/promocode/checkUsage' в бесконечность.")       
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit (0);
        while (true) {
            try {
                file_get_contents('http://localhost/payments/status');
                file_get_contents('http://localhost/payments/autoRevoke');
                file_get_contents('http://localhost/promocode/checkUsage');
		sleep(1);
            } catch (\Exception $e) {}
        }
    }
}