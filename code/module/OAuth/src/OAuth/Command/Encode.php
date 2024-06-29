<?php

namespace OAuth\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class Encode extends Command
{
    /** @var string */
    protected static $defaultName = 'oauth:encode';

    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->setDescription('Encode/hash the client secret')
            ->addArgument(
                'client_secret',
                InputArgument::REQUIRED,
                'Client secret string'
            )
        ;
    }

    /**
    * In CLI, you can extract the strings from a project (folder) into a .po to be translated
    * using services like poeditor.com
    *
    * @param InputInterface $input
    * @param OutputInterface $screen
    * @return {int|mixed}
    */
    protected function execute(InputInterface $input, OutputInterface $screen): int
    {
        $secret = $input->getArgument('client_secret');
        //$output = $input->getArgument('output');
        $screen->writeln('Converting the secret into a hash');

        $hash = password_hash($secret, PASSWORD_DEFAULT);
        if(!password_verify($secret, $hash)) {
            $screen->writeln('<error>ERROR</error> invalid secret');
            return 1;
        }
        $screen->writeln('<info>SUCCESS</info> '.PHP_EOL.'<comment>' . $hash. '</comment>');
        return 0;
    }
}
