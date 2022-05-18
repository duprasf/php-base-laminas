<?php
namespace TranslationExtractor\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TranslationExtractor\Model\Extractor;

class Extract extends Command
{
    /** @var string */
    protected static $defaultName = 'extract';

    protected function configure() : void
    {
        $this->setName(self::$defaultName);
        $this->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output file name');
        $this->addOption('input', null, InputOption::VALUE_REQUIRED, 'Input folder');
    }

    protected function execute(InputInterface $input, OutputInterface $screen) : int
    {
        $folder = $input->getOption('input');
        $output = $input->getOption('output');
        $screen->writeln('Extracting "' . $folder. '" into "'.$output.'"');

        // these are legacy params not set in Laminas
        $all = false;
        $form = false;

        $extractor = new Extractor();

        if($all) {
            $extractor->extractAllModules($folder, $output);
        }
        else if($form) {
            $filter = new \Laminas\Filter\Word\DashToCamelCase();
            $upper = $filter->filter($form);

            $filter = new \Laminas\Filter\Word\CamelCaseToDash();
            $lower = strtolower($filter->filter($form));

            if(!$folder || !file_exists($folder.'/Module.php')) {
                print "You must provide a valid module folder as a start point for the form".PHP_EOL;
            }
            else {
                $fileList = array(
                    $folder."/config/autoload/{$lower}.local.php",
                    $folder."/config/autoload/{$lower}.global.php",
                    $folder."/src/".basename($folder)."/Controller/{$upper}Controller.php",
                    $folder."/src/".basename($folder)."/Model/{$upper}.php",
                    $folder."/view/".strtolower($filter->filter(basename($folder)))."/{$lower}",
                );
                $extractor->processFileList($fileList, $output);
            }
        }
        else {
            $extractor->processFolder($folder, $output);
        }
        return 0;
    }
}
