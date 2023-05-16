<?php
namespace Application\View\Helper;

use Laminas\View\Helper\HelperInterface;
use Laminas\View\Renderer\RendererInterface;

/**
* This is a helper to strip all HTML content but replace <abbr> with the full title
*/
class StripTags implements HelperInterface
{
    protected $view;
    public function setView(RendererInterface $view) {$this->view = $view;}
    public function getView() {return $this->view;}

    public function __invoke($string)
    {
        return trim(strip_tags(str_replace('"', '&quot;', preg_replace('(<abbr [^>]*title="([^"]*)"[^>]*>(?:(?!</abbr>).)*</abbr>)i', '\1', $string))));
    }
}