<?php

namespace Application\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\I18n\Translator as MvcTranslator;

class GetTranslator extends AbstractPlugin
{
    /**
    * @var MvcTranslator
    */
    protected $translator = null;

    public function setTranslator(MvcTranslator $mvcTranslator)
    {
        $this->translator = $mvcTranslator;
        return $this;
    }
    public function getTranslator()
    {
        return $this->translator;
    }

    protected $lang;
    public function setLang($lang)
    {
        $this->lang = $lang;
        return $this;
    }
    public function getLang()
    {
        return $this->lang;
    }

    public function __call($name, $args)
    {
        return $this->getTranslator()->$name(...$args);
    }

    public function __construct(MvcTranslator $mvcTranslator, string $lang)
    {
        $this->setTranslator($mvcTranslator)
            ->setLang($lang)
        ;
    }
}
