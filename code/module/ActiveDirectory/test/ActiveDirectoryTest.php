<?php

declare(strict_types=1);

namespace ActiveDirectoryTest;

use PHPUnit\Framework\TestCase;
use ActiveDirectory\Model\ActiveDirectory;

final class ActiveDirectoryTest extends TestCase
{
    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];
exit(basename(__FILE__).':'.__LINE__.PHP_EOL);

        $this->setApplicationConfig(ArrayUtils::merge(
            include dirname(dirname(dirname(dirname(__DIR__)))) . '/config/application.config.php',
            $configOverrides
        ));

        parent::setUp();
    }

    public function testAttributesAreEmptyByDefault(): void
    {
        $element = new ActiveDirectory();
        self::assertEquals([], $element->getAttributes());
    }
}
