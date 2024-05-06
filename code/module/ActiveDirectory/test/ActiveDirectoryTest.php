<?php

declare(strict_types=1);

namespace ActiveDirectoryTest;

use PHPUnit\Framework\TestCase;
use Laminas\Stdlib\ArrayUtils;
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
        $config = ArrayUtils::merge(
            include dirname(dirname(dirname(__DIR__))) . '/config/application.config.php',
            $configOverrides
        );

        $this->setLdaps();

        parent::setUp();
    }

    public function testValidateUsername(): void
    {
        $element = new ActiveDirectory();
        self::assertEquals('fdupras', $element->validateUsername('fdupras'));
    }

    public function testInvalidateUsernames(): void
    {
        $element = new ActiveDirectory();
        self::assertEquals(false, $element->validateUsername('invalide user name'));
        self::assertEquals(false, $element->validateUsername('invalide user name'));
    }
}
