<?php

namespace UserAuth\Trait;

use UserAuth\Model\User\UserInterface;

trait UserAwareTrait
{
    private $user;
    public function setUser(UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    protected function getUser(): UserInterface
    {
        return $this->user;
    }
}
