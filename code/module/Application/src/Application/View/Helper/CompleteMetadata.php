<?php

namespace Application\View\Helper;

use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\I18n\Translator;
use Application\Model\Breadcrumbs;
use Application\Model\Metadata;

class CompleteMetadata implements \Laminas\View\Helper\HelperInterface
{
    public function __invoke(PhpRenderer|ViewModel $layout)
    {
        if($layout instanceof PhpRenderer) {
            $layout->vars()['cdts'] = $this->getCDTS();
            $metadata = $layout->vars('metadata');
            if(!$metadata instanceof Metadata) {
                $metadata = $this->getMetadataObj()->completeMetadata($metadata);
            } else {
                $metadata->completeMetadata();
            }
            return $metadata;
        }
        if($layout instanceof ViewModel) {
            $views = $layout->getChildren();
            if(isset($views[0])) {
                return $this->updateMetadata($layout, $views[0]);
            }
        }
    }

    protected function updateMetadata($layout, $view)
    {
        $metadata = $view->getVariable('metadata');
        try {
            if(!$metadata instanceof Metadata) {
                $metadata = $this->getMetadataObj()->completeMetadata($metadata);
            } else {
                $metadata->completeMetadata();
            }
        } catch(\Exception $e) {
            var_dump($e->getMessage());
            exit(basename(__FILE__).':'.__LINE__.PHP_EOL);

        }

        $layout->setVariables($metadata);
        $layout->setVariable('breadcrumbItems', $view->breadcrumbItems);

        $layout->setVariable('cdts', $this->getCDTS());
        if($view->getVariable('switch-lang-url')) {
            $layout->setVariable('switch-lang-url', $view->getVariable('switch-lang-url'));
        }
        return $metadata;
    }

    protected $view;
    public function setView(RendererInterface $view)
    {
        $this->view = $view;
    }
    public function getView()
    {
        return $this->view;
    }

    private $cdts;
    public function setCDTS(array $cdts)
    {
        $this->cdts = $cdts;
        return $this;
    }
    public function getCDTS()
    {
        return $this->cdts;
    }

    private $metadata;
    public function setMetadataObj(Metadata $obj)
    {
        $this->metadata = $obj;
        return $this;
    }
    protected function getMetadataObj()
    {
        return $this->metadata;
    }
}
