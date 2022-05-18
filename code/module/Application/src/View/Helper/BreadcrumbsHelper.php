<?php
namespace Application\View\Helper;

use \Laminas\Mvc\I18n\Translator;
use \Laminas\View\Renderer\RendererInterface;
use \Laminas\View\Helper\HelperInterface;
use \Application\Model\Breadcrumbs;

class BreadcrumbsHelper implements HelperInterface
{
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

    protected $translator;
    public function setTranslator(Translator $translator) {$this->translator = $translator;}
    public function getTranslator() {return $this->translator;}

    protected $breadcrumbs;
    public function setBreadcrumbs(?Breadcrumbs $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
    }
    public function getBreadcrumbs()
    {
        return $this->breadcrumbs ?? new Breadcrumbs();
    }

    public function __invoke(?Breadcrumbs $breadcrumbs) {
        if($breadcrumbs) {
            $this->setBreadcrumbs($breadcrumbs);
        }
        return $this;
    }

    public function __toString()
    {
        $translator = $this->getTranslator();
        $breadcrumbs = $this->getBreadcrumbs()->getArrayCopy();
        $string = '<nav role="navigation" id="wb-bc" property="breadcrumb">
            <h2>'.$translator->translate('You are here:').'</h2>
                <div class="container">
                    <div class="row">
                        <ol class="breadcrumb">';
                            foreach($breadcrumbs as $crumb){
                                $string.='<li><a href="'.$crumb['href'].'">';
                                if(isset($crumb['acronym'])) {
                                    $string.='<abbr title="'.$crumb['acronym'].'">'.$crumb['title'].'</abbr>';
                                }
                                else {
                                    $string.=$crumb['title'];
                                }
                                $string.='</a></li>';
                            }
                           	$string.= '
                        </ol>
                    </div>
                </div>
            </nav>
        ';
        return $string;
    }

    public function tojson()
    {
        $breadcrumbs = $this->getArrayCopy();
        return json_encode($breadcrumbs);
    }
}
