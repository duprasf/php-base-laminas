<?php

namespace Application\View\Helper;

use JsonSerializable;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Helper\HelperInterface;
use Application\Model\Breadcrumbs;

/**
* Generate the WET HTML for the breadcrumbs
*/
class BreadcrumbsHelper implements HelperInterface, JsonSerializable
{
    public function __invoke(null|array|Breadcrumbs $breadcrumbs = null): self
    {
        if($breadcrumbs) {
            $this->setBreadcrumbs($breadcrumbs);
        }
        return $this;
    }

    /**
    * Return the WET HTML for the breadcrumbs
    *
    * @return string
    */
    public function __toString(): string
    {
        $translator = $this->getTranslator();
        $breadcrumbs = $this->getBreadcrumbs();
        if(!is_array($breadcrumbs)) {
            $breadcrumbs = $breadcrumbs->getArrayCopy();
        }
        $innerString='';
        foreach($breadcrumbs as $crumb) {
            $innerString .= '<li><a href="'.$crumb['href'].'">';
            if(isset($crumb['acronym'])) {
                $innerString .= '<abbr title="'.$crumb['acronym'].'">'.$crumb['title'].'</abbr>';
            } else {
                $innerString .= $crumb['title'];
            }
            $innerString .= '</a></li>';
        }
        $string = '<nav role="navigation" id="wb-bc" property="breadcrumb">
            <h2>'.$translator->translate('You are here:').'</h2>
                <div class="container">
                    <div class="row">
                        <ol class="breadcrumb">'
                        .$innerString.
                        '</ol>
                    </div>
                </div>
            </nav>
        ';
        return $string;
    }

    public function tojson(): string
    {
        return json_encode($this->getBreadcrumbs());
    }

    public function jsonSerialize(): mixed
    {
        return $this->getBreadcrumbs()->getArrayCopy();
    }

    protected $view;
    public function setView(RendererInterface $view): self
    {
        $this->view = $view;
        return $this;
    }
    public function getView(): RendererInterface
    {
        return $this->view;
    }

    protected $translator;
    public function setTranslator(Translator $translator): self
    {
        $this->translator = $translator;
        return $this;
    }
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    protected $breadcrumbs;
    public function setBreadcrumbs(null|array|Breadcrumbs $breadcrumbs = null): self
    {
        $this->breadcrumbs = $breadcrumbs;
        return $this;
    }
    public function getBreadcrumbs(): array|Breadcrumbs
    {
        return $this->breadcrumbs ?? new Breadcrumbs();
    }
}
