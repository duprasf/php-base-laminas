<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Stdlib\ArrayUtils;

/**
* @ignore Test class, no need to be in documentation
*/
class IndexControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        $this->setApplicationConfig(include '/var/www/config/application.config.php');
        parent::setUp();
    }

    public function testDefaultModulesAreLoaded(): void
    {
        $this->assertModulesLoaded(['Application', 'AutoStats', 'PublicAsset']);
    }

    public function testRootPageCanBeLoaded(): void
    {
        if(!count($GLOBALS['modulesInAppsFolder'])) {
            $this->assertCount(0, $GLOBALS['modulesInAppsFolder']);
            return;
        }
        $this->dispatch('/', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertNotModuleName('Application', 'Your app should setup a splash page or index page');
    }

    public function testEnHomePageCanBeAccessed(): void
    {
        if(!count($GLOBALS['modulesInAppsFolder'])) {
            $this->assertCount(0, $GLOBALS['modulesInAppsFolder']);
            return;
        }
        $this->dispatch('/en', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertNotModuleName('Application', 'Your app should have a /en page');
    }

    public function testFrHomePageCanBeAccessed(): void
    {
        if(!count($GLOBALS['modulesInAppsFolder'])) {
            $this->assertCount(0, $GLOBALS['modulesInAppsFolder']);
            return;
        }
        $this->dispatch('/fr', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertNotModuleName('Application', 'Your app should have a /fr page');
    }

    public function testInvalidRouteDoesNotCrash(): void
    {
        $this->dispatch('/invalid/route', 'GET');
        $this->assertResponseStatusCode(404);
    }

    public function testBasescript(): void
    {
        $this->dispatch('/js/basescript.js', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertTemplateName('application/index/basescript');
        $this->assertResponseHeaderContains('Content-Type', 'application/javascript');
    }

    public function testBasescriptNotWanted(): void
    {
        $configOverrides = ['service_manager'=>['services'=>['loadBaseScript'=>false]]];
        $this->setApplicationConfig(ArrayUtils::merge(
            include '/var/www/config/application.config.php',
            $configOverrides
        ));
        $this->dispatch('/js/basescript.js', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertNotTemplateName('application/index/basescript');
    }
}
