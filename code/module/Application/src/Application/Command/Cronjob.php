<?php

namespace Application\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Laminas\EventManager\EventManagerInterface as EventManager;

class Cronjob extends Command
{
    /**
    * @var EventManager
    * @internal
    */
    protected $eventManager;
    /**
    * should be used in the factory
    *
    * @param EventManager $manager
    * @return User
    */
    public function setEventManager(EventManager $manager)
    {
        $this->eventManager = $manager;
        return $this;
    }
    /**
    * Get the EventManager to triger for different events
    *
    * @return EventManager
    */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /** @var string */
    protected static $defaultName = 'cronjob';

    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->setDescription('Trigger a cronjob even that can be picked up by other modules.')
            //->addArgument('input',InputArgument::REQUIRED,'The name of the file to load')
        ;
        //$this->addOption('input', null, InputOption::VALUE_REQUIRED, 'Input folder');
        //$this->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output file name');
    }

    /**
    * @param InputInterface $input
    * @param OutputInterface $screen
    * @return {int|mixed}
    */
    protected function execute(InputInterface $input, OutputInterface $screen): int
    {
        try {
            //$file = $input->getArgument('input');
            $screen->writeln('Triggering cronjob for all modules');

            // trigger an event that the user is about to logout

            $this->getEventManager()->trigger(
                'cronjob',
                $this,
                [
                    'timestamp' => time(),
                    'minute' => date('i'),
                ]
            );

            $screen->writeln('');
            $screen->writeln('<info>SUCCESS</info> Cronjob ended successfully.');
        } catch(Exception $e) {
            $screen->writeln('<error>ERROR</error> '.$e->getMessage());
        }
        return 0;
    }
}
