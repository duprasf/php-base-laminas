<?php
namespace DompdfView\Renderer;

use Laminas\View\Renderer\RendererInterface as Renderer;
use Laminas\View\Resolver\ResolverInterface as Resolver;
use Dompdf\Dompdf;

class ViewPdfRenderer implements Renderer
{
    private $dompdf;
    private $resolver;
    private $htmlRenderer;

    public function setHtmlRenderer(Renderer $renderer)
    {
        $this->htmlRenderer = $renderer;
        return $this;
    }
    public function getHtmlRenderer()
    {
        return $this->htmlRenderer;
    }

    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;
        return $this;
    }
    public function getResolver()
    {
        return $this->resolver;
    }

    public function setEngine(Dompdf $dompdf)
    {
        $this->dompdf = $dompdf;
        return $this;
    }
    public function getEngine()
    {
        return $this->dompdf;
    }

    /**
     * Renders values as a PDF
     *
     * @param  string|Model $nameOrModel The script/resource process, or a view model
     * @param  null|array|\ArrayAccess Values to use during rendering
     * @return string The script output.
     */
    public function render($nameOrModel, $values = null)
    {
        if(!is_string($nameOrModel)) {
            $paperSize = $nameOrModel->getOption('paperSize');
            $paperOrientation = $nameOrModel->getOption('paperOrientation');
            $basePath = $nameOrModel->getOption('basePath');
            $nameOrModel->setVariable('basePath', $basePath);
            $chroot = $nameOrModel->getOption('chroot');
            $context = $nameOrModel->getOption('context');
        }
        else{
            $paperSize = 'letter';
            $paperOrientation = 'portrait';
            $basePath = __DIR__;
            $chroot = '/var/www';
            $context = stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => FALSE,
                    'verify_peer_name' => FALSE,
                    'allow_self_signed'=> TRUE
                )
            ));
        }

        //$pdf = $this->getEngine();
        // can't explain why in French using the dompdf created in the factory
        // doesn't work when opening in acrobat
        $pdf = new Dompdf(['chroot'=>$chroot]);
        $pdf->setPaper($paperSize, $paperOrientation);
        $pdf->setBasePath($basePath);
        $pdf->setHttpContext($context);

        $html = $this->getHtmlRenderer()->render($nameOrModel, $values);
        $pdf->loadHtml($html);
        $pdf->render();

        return $pdf->output();
    }
}
