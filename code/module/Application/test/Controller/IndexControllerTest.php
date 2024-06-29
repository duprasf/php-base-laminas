<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\IndexController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

/**
* @ignore Test class, no need to be in documentation
*/
class IndexControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include dirname(dirname(dirname(__DIR__))) . '/config/application.config.php',
            $configOverrides
        ));

        parent::setUp();
    }

    public function testHomePageCanBeAccessed(): void
    {
        $this->dispatch('/', 'GET');
        $this->assertResponseStatusCode(200);
        //$this->assertModuleName('application');
        //$this->assertControllerName(IndexController::class); // as specified in router's controller name alias
        //$this->assertControllerClass('IndexController');
        //$this->assertMatchedRouteName('home');
    }

    public function testEnHomePageCanBeAccessed(): void
    {
        $this->dispatch('/en', 'GET');
        $this->assertResponseStatusCode(200);
    }

    public function testFrHomePageCanBeAccessed(): void
    {
        $this->dispatch('/fr', 'GET');
        $this->assertResponseStatusCode(200);
    }


    public function testInvalidRouteDoesNotCrash(): void
    {
        $this->dispatch('/invalid/route', 'GET');
        $this->assertResponseStatusCode(404);
    }
    /*
        public function testIndexActionCanBeAccessed(): void
        {
            $this->dispatch('/', 'GET');
            $this->assertResponseStatusCode(200);
            $this->assertModuleName('application');
            $this->assertControllerName(IndexController::class); // as specified in router's controller name alias
            $this->assertControllerClass('IndexController');
            $this->assertMatchedRouteName('home');
        }

        public function testIndexActionViewModelTemplateRenderedWithinLayout(): void
        {
            $this->dispatch('/', 'GET');
            $this->assertQuery('.container .jumbotron');
        }
    /**/
}
