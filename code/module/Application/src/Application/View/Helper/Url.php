<?php

namespace Application\View\Helper;

use Laminas\View\Helper\Url as UrlHelper;
use Laminas\View\Renderer\RendererInterface;

/**
* Basic URL View Helper class but will set the locale/language of the requested
* route automatically if not set when called
*/
class Url extends UrlHelper
{
    public function __invoke(
        $name = null,
        $params = [],
        $options = [],
        $reuseMatchedParams = false
    ) {
        if(!is_array($params)) {
            $params = array();
        }
        if(!isset($params['lang'])) {
            $params['lang'] = $this->getLang();
        }
        if(!isset($params['locale'])) {
            $params['locale'] = $params['lang'];
        }
        if(!isset($options['locale'])) {
            $options['locale'] = $params['locale'];//.'_CA';
        }
        return parent::__invoke($name, $params, $options, $reuseMatchedParams);
    }

    protected $view;
    public function setView(RendererInterface $view)
    {
        $this->view = $view;
        return $this;
    }
    public function getView()
    {
        return $this->view;
    }

    protected $lang;
    public function setLang(string $lang)
    {
        $this->lang = $lang;
        return $this;
    }
    public function getLang(): string|null
    {
        return $this->lang;
    }
}
