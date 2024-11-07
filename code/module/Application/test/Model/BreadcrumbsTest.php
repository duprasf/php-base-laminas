<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Model\Breadcrumbs;

final class BreadcrumbsTest extends AbstractHttpControllerTestCase
{
    protected $breadcrumbs;

    public function setUp(): void
    {
        $this->setApplicationConfig(include '/var/www/config/application.config.php');
        parent::setUp();
        $container = $this->getApplicationServiceLocator();
        $this->breadcrumbs = $container->get(Breadcrumbs::class);
    }

    public function testBreadcrumbsStartsEmpty(): void
    {
        $this->assertEquals(0, count($this->breadcrumbs));
    }

    public function testBreadcrumbsHasOneItem(): void
    {
        $breadcrumbs = $this->breadcrumbs;
        $breadcrumbs->addBreadcrumbs(['http://canada.ca/en'=>'canada.ca']);
        $this->assertEquals(1, count($this->breadcrumbs));
        $breadcrumbs(['http://canada.ca/en'=>'canada.ca']);
        $this->assertEquals(1, count($this->breadcrumbs));
    }

    public function testBreadcrumbsAddOneItem(): void
    {
        $breadcrumbs = $this->breadcrumbs;
        $breadcrumbs(['http://canada.ca/en'=>'canada.ca']);
        $this->assertEquals(1, count($this->breadcrumbs));
        $breadcrumbs->addBreadcrumbs(['http://health.canada.ca/en'=>'second page']);
        $this->assertEquals(2, count($this->breadcrumbs));
    }

    public function testBreadcrumbsToJson(): void
    {
        $this->assertJson($this->breadcrumbs->tojson());
        $this->breadcrumbs->addBreadcrumbs(['canada.ca'=>'http://canada.ca/en']);
        $this->assertJson($this->breadcrumbs->tojson());
    }
}
