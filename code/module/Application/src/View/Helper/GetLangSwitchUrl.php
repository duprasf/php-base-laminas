<?php
namespace Application\View\Helper;

use \Laminas\Router\Http\RouteMatch;

class GetLangSwitchUrl implements \Laminas\View\Helper\HelperInterface
{
    protected $view;
    public function setView(\Laminas\View\Renderer\RendererInterface $view) {$this->view = $view;}
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