<?php
namespace DompdfView\Model;

use Laminas\View\Model\ViewModel;

class PdfModel extends ViewModel
{
    protected $options = array(
        'paperSize' => 'Letter',
        'paperOrientation' => 'portrait',
        'basePath' => '/',
        'fileName' => null,
        'context' => null,
    );

    public function __construct()
    {
        $this->options['context'] = stream_context_create(array(
            'ssl' => array(
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed'=> TRUE
            )
        ));
    }

    public function setPdfOption($name, $val)
    {
        $this->options[$name] = $val;
        return $this;
    }

    /**
     * PDF probably won't need to be captured into a
     * a parent container by default.
     *
     * @var string
     */
    protected $captureTo = null;

    /**
     * PDF is usually terminal
     *
     * @var bool
     */
    protected $terminate = true;
}
