<?php

namespace Application\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Application\Model\Metadata;
use Application\Model\Breadcrumbs;

class CommonMetadata extends AbstractPlugin
{
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

    protected $url;
    public function setUrlObj($obj)
    {
        $this->url = $obj;
        return $this;
    }
    public function getUrlObj()
    {
        return $this->url;
    }

    protected $breadcrumbsObj;
    public function setBreadcrumbsObj($breadcrumbObj)
    {
        $this->breadcrumbsObj = $breadcrumbObj;
        return $this;
    }
    protected function getBreadcrumbsObj()
    {
        return $this->breadcrumbsObj;
    }

    protected $route;
    public function setRouteMatch($route)
    {
        $this->route = $route;
        return $this;
    }
    protected function getRouteMatch()
    {
        return $this->route;
    }

    protected $metadata;
    public function setMetadataObj(Metadata $obj)
    {
        $this->metadata = $obj;
        return $this;
    }
    public function getMetadataObj()
    {
        return $this->metadata;
    }

    private $appMetadata;
    public function setAppMetadata(array $metadata)
    {
        $this->appMetadata = $metadata;
        return $this;
    }
    protected function getAppMetadata()
    {
        return $this->appMetadata ?? [];
    }

    public function __invoke(ViewModel $view)
    {
        $translator = $this->getTranslator();
        $lang = $this->getLang();
        $url = $this->getUrlObj();
        $array = [
            "title" => $translator->translate('Default Application'),
            "appName" => $translator->translate('Default Application'),
            "description" => $translator->translate("Default Application"),
            "versionNumber" => '1.0',
            "isApp" => false,
            "contactLinks" => ["mailto:".getenv('ADMIN_EMAIL')],
            "showShare" => false,
            "showFeedback" => false,
            "appUrl" => $url('root'),
            "extra-css" => [],
            "extra-js" => [],
        ];

        $view->setVariable('metadata', $this->getMetadataObj()->merge($array));

        $view->setVariable('attribution', 'HC');

        /*
        $view->setVariable('userSession', [
            'enabled' => true,
            'events' => [
                'signin' => 'signin-pressed',
                'signout' => 'signout-pressed',
            ],
            'urls' => [
                //'signin'=>'',
                //'signout'=>'',
            ],
            'buttons' => [
                'settings' => [
                    'text' => $translator->translate('Settings'),
                    'icon' => 'glyphicon-wrench',
                    'url' => sprintf('/%s/apm-lite/user-settings', $lang),
                ],
            ],
            'session-length' => [
                'useJWT' => true,
                'JWT-name' => 'jwt',
            ],
        ]);
        /**/

        $breadcrumbs = $this->getBreadcrumbsObj();
        $breadcrumbItems = [
            'http://canada.ca/'.$lang => 'Canada.ca',
            // put the default breadcrumbs for your app here (in French)
            $url('root') => $translator->translate('Default Application'),
        ];
        $breadcrumbs($breadcrumbItems);
        $view->setVariable('breadcrumbItems', $breadcrumbs);

        $route = $this->getRouteMatch();

        $routeName = $route->getMatchedRouteName();
        $routeParams = $route->getParams();
        $url = $this->getUrlObj();

        $otherLang = $this->getLang() == 'en' ? 'fr' : 'en';
        $params = $routeParams;
        $params['locale'] = $otherLang;
        $params['lang'] = $otherLang;
        $view->setVariable(
            'switch-lang-url',
            $url(
                $routeName,
                $params,
                array('locale' => $otherLang, 'translator' => $translator)
            )
        );

        return $view;
    }
}
