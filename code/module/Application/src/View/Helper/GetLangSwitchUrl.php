<?php
namespace Application\View\Helper;

use Laminas\Router\Http\RouteMatch;
use Laminas\View\Helper\HelperInterface;
use Laminas\View\Renderer\RendererInterface;

/**
* This class will return the URL for the current page in the other official language
*/
class GetLangSwitchUrl implements HelperInterface
{
    protected $view;
    public function setView(RendererInterface $view) {$this->view = $view;}
    public function getView() {return $this->view;}

    protected $route;
    public function setRouteMatch(RouteMatch $route) {
        $this->route = $route;
        return $this;
    }
    public function getRouteMatch() {
        return $this->route;
    }

    public function __invoke()
    {
        $view = $this->getView();
        // set switchLang link
        $switchLangUrl = $view->vars('switch-lang-url');
        if(!$switchLangUrl) {
            $route = $this->getRouteMatch();
            if($route) {
                $params = $route->getParams();
                $params['locale'] = $view->lang == 'en' ? 'fr' : 'en';
                $switchLangUrl = $view->url($route->getMatchedRouteName(), $params);
            } else {
                $switchLangUrl='/';
            }
        }
        return $switchLangUrl;
    }
}