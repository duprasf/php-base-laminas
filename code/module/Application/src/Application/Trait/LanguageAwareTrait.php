<?php

namespace Application\Trait;

trait LanguageAwareTrait
{
    private $lang;
    public function setLang(string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }
    protected function getLang(): string
    {
        return $this->lang;
    }
}
