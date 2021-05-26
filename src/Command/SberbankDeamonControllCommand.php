<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SberbankDeamonControllCommand extends Command
{

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure()
    {
        $this
		->setName('sberbank:deamon:controll')
		->setDescription("Контролирует 'sberbank:deamon' и перезапускает при необходимости.")
	;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	while (true) {
    		try {
        		$commands = explode("\n", shell_exec('ps aux | grep php'));
    			$find = false;
    			foreach ($commands as $line) {
    				$line= explode(" ", $line);
    				if ($line[count($line) - 1] == 'sberbank:deamon') {
    					$find = true;
					//print_r("It's alive...\n");
    				}
    			}
    
    			if (! $find) {
				shell_exec('php bin/console sberbank:deamon &');
				//print_r("Alive again...\n");
			}
    		} catch (\Exception $e) {
        		
    		}
		
		sleep(10);
	}
    }
}