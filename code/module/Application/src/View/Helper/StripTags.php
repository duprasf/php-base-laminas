<?php
namespace Application\View\Helper;

class StripTags implements \Laminas\View\Helper\HelperInterface
{
    protected $view;
    public function setView(\Laminas\View\Renderer\RendererInterface $view) {$this->view = $view;}
    public function getView() {return $this->view;}

    public function __invoke($string)
    {
        return trim(strip_tags(str_replace('"', '&quot;', preg_replace('(<abbr [^>]*title="([^"]*)"[^>]*>(?:(?!</abbr>).)*</abbr>)i', '\1', $string))));
    }
}