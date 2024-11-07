<?php
namespace Application\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Composer\ClassMapGenerator\ClassMapGenerator;

class GeneratesAutoloadMap extends Command
{
    /** @var string */
    protected static $defaultName = 'generate-autoload-map';

    protected function configure() : void
    {
        $this->setName(self::$defaultName)
            ->setDescription('Generates the autoload map used by Laminas to speed up load time.')
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Scan both the apps/ and module/ folder. By default only the apps/ folder is parsed, use --all to generate maps for module/ as well.'
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
    protected function execute(InputInterface $input, OutputInterface $screen) : int
    {
        try {
            $screen->writeln('Scanning apps/ folder');

            // use glob to find apps
            $folders = glob(realpath('/var/www/apps').DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);

            $screen->writeln('Found '.count($folders).' folders in apps/');
            if($input->getOption('all')) {
                $modFolders = glob(realpath('/var/www/module').DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
                $screen->writeln('and '.count($modFolders).' folders in module/');
                $folders = array_merge($folders, $modFolders);
            }
print __LINE__.PHP_EOL;
error_reporting(E_ALL);
ini_set('display_errors', 1);

            $progressBar=new ProgressBar($screen, count($folders));
            $progressBar->start();

            foreach($folders as $f){
                $file = $f.DIRECTORY_SEPARATOR."autoload_classmap.php";
                $map = ClassMapGenerator::createMap($f);
                print __LINE__.PHP_EOL;
                if(!$map) {
                    print __LINE__.PHP_EOL;
                    continue;
                }
                print __LINE__.PHP_EOL;
                file_put_contents($file, '<?php'.PHP_EOL.PHP_EOL.'return '.var_export($map, true).';'.PHP_EOL);
                print __LINE__.PHP_EOL;
                $progressBar->advance();
                print __LINE__.PHP_EOL;
            }
            print __LINE__.PHP_EOL;

            $screen->writeln('');
            $screen->writeln('<info>SUCCESS</info>');
        } catch(\Exception $e) {
            $screen->writeln('<error>ERROR</error> '.$e->getMessage());
        }
        return 0;
    }
}
