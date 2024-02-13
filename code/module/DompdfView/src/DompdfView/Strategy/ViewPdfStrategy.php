<?php
namespace DompdfView\Strategy;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\View\ViewEvent;
use DompdfView\Model\PdfModel;
use DompdfView\Renderer\ViewPdfRenderer;

class ViewPdfStrategy implements ListenerAggregateInterface
{
    /**
    * @var \Zend\Stdlib\CallbackHandler[]
    */
    protected $listeners = array();

    /**
    * @var PdfRenderer
    */
    protected $renderer;

    /**
    * Constructor
    *
    * @param  PdfRenderer $renderer
    * @return void
    */
    public function __construct(ViewPdfRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Attach the aggregate to the specified event manager
     *
     * @param  EventManagerInterface $events
     * @param  int $priority
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, array($this, 'selectRenderer'), $priority);
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, array($this, 'injectResponse'), $priority);
    }

    /**
     * Detach aggregate listeners from the specified event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Detect if we should use the PdfRenderer based on model type
     *
     * @param  ViewEvent $e
     * @return null|PdfRenderer
     */
    public function selectRenderer(ViewEvent $e)
    {
        $model = $e->getModel();

        if ($model instanceof PdfModel) {
            return $this->renderer;
        }

        return;
    }

    /**
     * Inject the response with the PDF payload and appropriate Content-Type header
     *
     * @param  ViewEvent $e
     * @return void
     */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            // Discovered renderer is not ours; do nothing
            return;
        }

        $result = $e->getResult();

        if (!is_string($result)) {
            // @todo Potentially throw an exception here since we should *always* get back a result.
            return;
        }

        $response = $e->getResponse();
        $response->setContent($result);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/pdf');

        $filename = $e->getModel()->getOption('filename');
        if (!isset($filename)) {
            return;
        }
        $parts = pathinfo($filename);
        $filename = $parts['basename'].'.pdf';

        $response->getHeaders()->addHeaderLine(
            'Content-Disposition',
            'attachment; filename=' . $filename
        );
    }
}
