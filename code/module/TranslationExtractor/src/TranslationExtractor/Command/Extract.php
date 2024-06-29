<?php

namespace TranslationExtractor\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use TranslationExtractor\Model\Extractor;

class Extract extends Command
{
    /** @var string */
    protected static $defaultName = 'translation:extract';

    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->setDescription('Extract translations to a .po file')
            ->addArgument(
                'input',
                InputArgument::REQUIRED,
                'Which folder do you want to scan?'
            )
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'The name of the file to store the strings?'
            )
        ;
        //$this->addOption('input', null, InputOption::VALUE_REQUIRED, 'Input folder');
        //$this->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output file name');
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
        $folder = $input->getArgument('input');
        $output = $input->getArgument('output');
        $screen->writeln('Extracting "<comment>' . $folder. '</comment>" into "<info>'.$output.'</info>"');

        // these are legacy params not set in Laminas
        $all = false;
        $form = false;

        $extractor = new Extractor();

        try {
            $extractor->processFolder($folder, $output);
            $screen->writeln('<info>SUCCESS</info> the file '.$output.' was created with the strings found in <comment>' . $folder. '</comment>');
        } catch(\Exception $e) {
            $screen->writeln('<error>ERROR</error> '.$e->getMessage());
        }
        return 0;
    }
}
