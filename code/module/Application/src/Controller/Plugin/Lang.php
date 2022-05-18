<?php
namespace Application\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class Lang extends AbstractPlugin
{
    protected $lang;
    public function setLang($lang) {$this->lang=$lang; return $this;}
    public function getLang() {return $this->lang;}

    public function __invoke()
    {
        return $this->lang;
    }

    public function __construct(string $lang)
    {
        $this->setLang($lang);
    }
}
