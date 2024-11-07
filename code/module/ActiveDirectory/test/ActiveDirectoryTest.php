<?php

declare(strict_types=1);

namespace ActiveDirectoryTest;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use ActiveDirectory\Model\ActiveDirectory;

final class ActiveDirectoryTest extends AbstractHttpControllerTestCase
{
    protected $emailToTest='francois.dupras@hc-sc.gc.ca';
    protected $usernameToTest='fdupras';

    public function setUp(): void
    {
        $this->setApplicationConfig(include '/var/www/config/application.config.php');
        parent::setUp();
    }

    protected $readyForLdap=false;
    protected function isReadyForLdap(): bool
    {
        if($this->readyForLdap) {
            return true;
        }
        $container = $this->getApplicationServiceLocator();
        $config = $container->get('Config');
        $fp=false;
        if(isset($config['service_manager']['services']['ldap-options'])) {
            foreach($config['service_manager']['services']['ldap-options'] as $ldap) {
                $ip = gethostbyname($ldap['host']);
                if(!filter_var($ip, FILTER_VALIDATE_IP)) {
                    continue;
                }
                $fp = fsockopen($ldap['host'], $ldap['port']??389);
                if($fp) {
                    break;
                }
            }
        }
        if(!$fp) {
            return false;
        }
        $this->readyForLdap=true;
        return true;
    }

    public function testActiveDirectoryValidateUsername(): void
    {
        if(!$this->isReadyForLdap()) {
            $this->assertTrue(true);
            return;
        }
        $container = $this->getApplicationServiceLocator();

        $ad = $container->get(ActiveDirectory::class);
        self::assertStringContainsString(
            'OU=User Accounts,OU=Accounts,OU=Health Canada,DC=ad,DC=hc-sc,DC=gc,DC=ca',
            $ad->validateUsername($this->usernameToTest)
        );
    }

    public function testActiveDirectoryGetByEmail(): void
    {
        if(!$this->isReadyForLdap()) {
            $this->assertTrue(true);
            return;
        }
        $container = $this->getApplicationServiceLocator();
        $ad = $container->get(ActiveDirectory::class);

        self::assertStringContainsStringIgnoringCase(
            $this->usernameToTest,
            $ad->getUserByEmail($this->emailToTest, returnFirstElementOnly: true)['account']
        );
    }

    public function testActiveDirectoryGetByUsername(): void
    {
        if(!$this->isReadyForLdap()) {
            $this->assertTrue(true);
            return;
        }
        $container = $this->getApplicationServiceLocator();
        $ad = $container->get(ActiveDirectory::class);
        self::assertContains(
            $this->emailToTest,
            $ad->getByUsername($this->usernameToTest, returnFirstElementOnly: true)
        );
    }

    public function testActiveDirectoryInvalidateUsernames(): void
    {
        if(!$this->isReadyForLdap()) {
            $this->assertTrue(true);
            return;
        }
        $container = $this->getApplicationServiceLocator();
        $ad = $container->get(ActiveDirectory::class);
        self::assertFalse($ad->validateUsername('invalide user name'));
    }
}
