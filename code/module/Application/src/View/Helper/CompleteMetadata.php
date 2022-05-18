<?php
namespace Application\View\Helper;

use \Laminas\View\Renderer\RendererInterface;
use \Laminas\Mvc\I18n\Translator;
use \Application\Model\Breadcrumbs;

class CompleteMetadata implements \Laminas\View\Helper\HelperInterface
{
    protected $view;
    public function setView(RendererInterface $view) {$this->view = $view;}
    public function getView() {return $this->view;}

    private $metadataBuilder = null;
    public function setMetadataBuilder($metadataBuilder) {$this->metadataBuilder = $metadataBuilder;}
    public function getMetadataBuilder() {return $this->metadataBuilder;}

    private $cdts;
    public function setCDTS(array $cdts) {
        $this->cdts=$cdts;
        return $this;
    }
    public function getCDTS() {
        return $this->cdts;
    }

    public function __invoke($layout) {
        $views = $layout->getChildren();
        if(isset($views[0])) {
            $view = $views[0];
            $metadataBuilder = $this->getMetadataBuilder();
            try {
                //$layout->setVariables($view->getVariables());
                $metadata = $metadataBuilder->getFullMetadata($view->getVariable('metadata'));
                $layout->setVariables($metadata);
                $layout->setVariable('breadcrumbItems', $view->breadcrumbItems);
                $layout->setVariable('cdts', $this->getCDTS());
                if($view->getVariable('switch-lang-url')) {
                    $layout->setVariable('switch-lang-url', $view->getVariable('switch-lang-url'));
                }
            }
            catch(\Exception $e) {}
        }
        return $this->getMetadataBuilder();
    }
}