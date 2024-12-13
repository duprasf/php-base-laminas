<?php

declare(strict_types=1);

use Laminas\Mvc\Application;
use Laminas\Stdlib\ArrayUtils;

$envFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'environment';
if(file_exists($envFolder) && is_dir($envFolder) && is_readable($envFolder)) {
    foreach(glob($envFolder.DIRECTORY_SEPARATOR.'*.env') as $file) {
        foreach(file($file) as $line) {
            if(!trim($line)) {
                continue;
            }
            putenv(trim($line));
        }
    }
}

if(getenv('PHP_DEV_ENV')) {
    ini_set('display_errors', true);
    error_reporting(E_ALL);
}

try {
    $GLOBALS['startTime'] = microtime(true);

    $root = getenv('LAMINAS_ROOT_PATH') ?: dirname(__DIR__);//'/var/www';
    /**
     * This makes our life easier when dealing with paths. Everything is relative
     * to the application root now.
     */
    chdir($root);

    // Decline static file requests back to the PHP built-in webserver
    if (php_sapi_name() === 'cli-server') {
        $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if (is_string($path) && __FILE__ !== $path && is_file($path)) {
            return false;
        }
        unset($path);
    }

    // Composer autoloading
    include $root.'/vendor/autoload.php';
    if (! class_exists(Application::class)) {
        throw new RuntimeException(
            "Unable to load application.\n"
            . "- Type `composer install` if you are developing locally.\n"
            . "- Type `vagrant ssh -c 'composer install'` if you are using Vagrant.\n"
            . "- Type `docker-compose run laminas composer install` if you are using Docker.\n"
        );
    }

    // Retrieve configuration
    $appConfig = require $root.'/config/application.config.php';
    if (file_exists($root.'/config/development.config.php')) {
        $appConfig = ArrayUtils::merge($appConfig, require $root.'/config/development.config.php');
    }

    // Run the application!
    Application::init($appConfig)->run();

} catch(\Exception $e) {
    if(getenv('PHP_DEV_ENV')) {
        print '<pre>'.$e->getMessage().'</pre>';
        print 'Stack:<pre>';
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        print '</pre>';
        var_dump($e->getMessage());
        var_dump($e->getFile().':'.$e->getLine());
    } else {
        print 'unknown error';
    }
}
