<?php
/**
* List of additional enabled modules for this application.
*
* This should be an array of module namespaces used in the application.
*/
$extraModules = file_exists(__DIR__.'/autoload/_modules.local.php')
    ? include(__DIR__.'/autoload/_modules.local.php')
    : array()
;

/**
* Every modules in apps/ is loaded automatically, this is for using
* this image as a container for a single app.
*/
$GLOBALS['modulesInAppsFolder'] = [];
if(!in_array('noAutoLoadApps', $extraModules)) {
    $GLOBALS['modulesInAppsFolder'] = glob(realpath(dirname(__DIR__).'/apps').DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
    $GLOBALS['modulesInAppsFolder'] = array_map('basename', $GLOBALS['modulesInAppsFolder']);
} else {
    array_splice($extraModules, array_search('noAutoLoadApps', $extraModules), 1);
}


$envModules = getExistingEnv('LAMINAS_LOAD_MODULES');
if($envModules) {
    try {
        $envModules = json_decode($envModules, true);

        if(json_last_error() == JSON_ERROR_NONE) {
            $extraModules = array_merge($extraModules, $envModules);
        }
    } catch(\Exception $e) {

    }
}

$modules = array_merge([
    'Laminas\Mvc\Plugin\FilePrg',
    'Laminas\Mvc\Plugin\FlashMessenger',
    'Laminas\Mvc\Plugin\Identity',
    'Laminas\Mvc\Plugin\Prg',
    'Laminas\Session',
    'Laminas\Mvc\I18n',
    'Laminas\I18n',
    'Laminas\Log',
    'Laminas\Form',
    'Laminas\Hydrator',
    'Laminas\InputFilter',
    'Laminas\Filter',
    'Laminas\Di',
    'Laminas\Router',
    'Laminas\Validator',
    'OAuth',
    'UserAuth',
    'ActiveDirectory',
    'Application',
    'PublicAsset',
    'TranslationExtractor',
    'AutoStats'
], $GLOBALS['modulesInAppsFolder'], $extraModules);

if(getenv('PHP_DEV_ENV') && isset($_GET['show_modules'])) {
    print '<pre>';
    var_dump($modules);
    print '</pre>';
}

return $modules;
