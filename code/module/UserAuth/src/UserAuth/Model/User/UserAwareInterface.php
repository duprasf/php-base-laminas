<?php

namespace UserAuth\Model\User;

interface UserAwareInterface
{
    public function setUser(UserInterface $user): self;
}
