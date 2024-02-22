<?php
namespace Application\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\View\Helper\Url;

class GetUrlHelper extends AbstractPlugin
{
    protected $urlObj;

    public function setUrl(Url $obj) {
        $this->urlObj = $obj;
        return $this;
    }
    public function getUrl() {
        return $this->urlObj;
    }

    public function __invoke(...$args)
    {
        if(count($args)) {
            return $this->getUrl()->__invoke(...$args);
        }
        return $this->getUrl();
    }

    public function __construct(Url $obj)
    {
        $this->setUrl($obj);
    }
}
