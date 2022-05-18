<?php
namespace Application\View\Helper;

class GetSwitchUrl implements \Laminas\View\Helper\HelperInterface
{
    protected $view;
    public function setView(\Laminas\View\Renderer\RendererInterface $view) {$this->view = $view;}
    public function getView() {return $this->view;}

    public function __invoke($string)
    {
        $view = $this->getView();
        // set switchLang link
        $otherLang = $lang == 'fr' ? 'en' : 'fr';
        $switchLangUrl = $view->getVariable('switch-lang-url');
        if(!$switchLangUrl) {
            $routeMatch = $view->getHelperPluginManager()->getServiceLocator()->get('Application')->getMvcEvent()->getRouteMatch();
            $params = $route->getParams();
            $params['locale'] = $lang == 'en' ? 'fr' : 'en';
            //$service
            //$layout->setVariable('switchLangUrlName', $route->getMatchedRouteName());
            //$layout->setVariable('switchLangUrlParams', $params);
            $switchLangUrl = $view->url($route->getMatchedRouteName(), $params);
        }
        return $switchLangUrl;
    }
}