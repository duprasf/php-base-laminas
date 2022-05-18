<?php
namespace Application\Model;

use \Laminas\Mvc\I18n\Translator;

class MetadataBuilder
{
    const ERROR_NO_METADATA = 1;

    const TEMPLATE_FULL_PAGE = 'full-content';
    const TEMPLATE_LEFT_MENU = 'content-with-left-menu';
    const TEMPLATE_SERVER_MESSAGE = 'server-message';
    const TEMPLATE_SERVER_MESSAGE_CURRENT_LANG = 'server-message-current-lang';
    const TEMPLATE_NO_VIEW = 'no-layout';
    const TEMPLATE_NO_LAYOUT = 'no-layout';

    const MENU_NO_MENU = 'no-menu';
    const STANDALONE = 'standalone';

    protected $data;
    private $ready = false;
    private $rootFolder = null;
    private $uri = null;

    protected $translator;
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
        return $this;
    }
    public function getTranslator()
    {
        return $this->translator;
    }
    public function translate($id)
    {
        return $this->translator->translate($id);
    }

    protected $defaultMetadata = [];
    public function setDefaultMetadata(array $data) {$this->defaultMetadata = $data; return $this;}
    public function getDefaultMetadata() { return $this->defaultMetadata;}

    protected $lang;
    public function setLang(string $lang) {$this->lang = $lang; return $this;}
    public function getLang() { return $this->lang;}

    public function getFullMetadata($metadata)
    {
        if($metadata instanceOf \ArrayObject) {
            $metadata = $metadata->getArrayCopy();
        }
        $data = $metadata;
        $default = $this->getDefaultMetadata();
        $error = array();
        $lang = $this->lang;

        // If needed, get request from factory
        //$data['uri'] = parse_url($request->getUri(), PHP_URL_PATH);

        if(!is_array($metadata) || count($metadata) == 0) {
            $error[] = $this->translate('no metadata');
            $data = $default;
        }
        else {
            foreach($default as $key=>$val) {
                if(!isset($metadata[$key])) {
                    $data[$key] = $val;
                }
            }
        }

        // SERVER PAGE
        if(isset($data['template']) && ($data['template'] == self::TEMPLATE_SERVER_MESSAGE || $data['template'] == self::TEMPLATE_SERVER_MESSAGE_CURRENT_LANG)) {
        }
        // NO VIEW, FOR AJAX REQUEST
        else if(isset($data['template']) && $data['template'] == self::TEMPLATE_NO_LAYOUT) {
        }
        // BASIC TEMPLATE
        else {
            if(!isset($data['titleH1']) && isset($data['title']))
                $data['titleH1'] = $data['title'];

            if(!isset($data['description'])) {
                $error[] = $this->translate('Invalid metadata description');
                $data['description'] = 'Infrastructure Canada';
            }

            if(!isset($data['issued']) || (isset($data['issued']) && !preg_match('(^\d{4}-\d{2}-\d{2}(?: \d{1,2}:\d{1,2}(?::\d{1,2})?)?$)', $data['issued']))) {
                $error[] = $this->translate('Issued date is invalid');
                $data['issued'] = time();
                $data['issuedTimestamp'] = time();
            }
            else {
                $data['issuedTimestamp'] = strtotime($data['issued']);
            }

            if(!isset($data['modified']) || !is_string($data['modified']) || $data['modified'] == '') {
                $data['modified'] = $data['issued'];
                $data['modifiedTimestamp'] = $data['issuedTimestamp'];
            }
            else if(isset($data['modified']) && !preg_match('(^\d{4}-\d{2}-\d{2}(?: \d{1,2}:\d{1,2}(?::\d{1,2})?)?$)', $data['modified'])) {
                $error[] = $this->translate('Modified date is invalid');
                $data['modified'] = $data['issued'];
                $data['modifiedTimestamp'] = $data['issuedTimestamp'];
            }
            else {
                $data['modifiedTimestamp'] = strtotime($data['modified']);
            }

            if($data['issuedTimestamp'] > $data['modifiedTimestamp']) {
                $error[] = $this->translate('Modified date is prior to the issued date');
                $data['modified'] = $data['issued'];
            }

            if(!isset($data['title']) && !isset($data['titleEng'])) {
                $error[] = $this->translate('Title is missing');
            }

            if(!isset($data['creator'])) {
                $data['creator'] = $this->translate('Government of Canada, Infrastructure Canada');
            }

            if(isset($data['breadcrumbs']) && !is_array($data['breadcrumbs'])) {
                $extraCrumbs = array();
                if(preg_match_all('(\(([^\|]*)\|([^\)]*)\)\s*)', $data['breadcrumbs'], $out, PREG_SET_ORDER)) {
                    foreach($out as $crumb) {
                        $extraCrumbs[] = array('href'=>$crumb[2], 'name'=>$crumb[1]);
                    }
                }
                $data['breadcrumbs'] = $extraCrumbs;
            }
        }
        if(isset($data['extra-css'])) {
            $extra = array();
            if(preg_match_all('(([^\|]*)(?:\s*\|\s*)?)', $data['extra-css'], $out)) {
                foreach($out[1] as $item) if(trim($item)) $extra[] = $item;
            }
            $data['extra-css'] = $extra;
        }
        if(isset($data['extra-js'])) {
            $extra = array();
            if(preg_match_all('(([^\|]*)(?:\s*\|\s*)?)', $data['extra-js'], $out)) {
                foreach($out[1] as $item) if(trim($item)) $extra[] = $item;
            }
            $data['extra-js'] = $extra;
        }

        /*
        // make sure the title always ends with INFRAnet
        // these patterns where used to remove Infrastructure Canada from the title to make
        // sure it would not be there twice. It is not needed on the INFRAnet
        $pattern = '(^(?:INFRAnet|Infrastructure Canada) *(?:-|&ndash;|&mdash;)? *)i';
        $pattern2 = '( *(?:-|&ndash;|&mdash;)? *(?:INFRAnet|Infrastructure Canada)$)i';
        if(isset($data['title'])) {
        //$data['title'] = preg_replace($pattern, '', preg_replace($pattern2, '', $data['title']));
        $data['title'].= (strlen(trim($data['title'])) > 0 ? ' - ':''). 'INFRAnet';
        //$data['titleH1'] = preg_replace($pattern, '', preg_replace($pattern2, '', $data['titleH1']));
        }
        else if(isset($data['titleEng'])) {
        //$data['titleEng'] = preg_replace($pattern, '', preg_replace($pattern2, '', $data['titleEng']));
        $data['titleEng'].= (strlen(trim($data['titleEng'])) > 0 ? ' - ':''). 'INFRAnet';
        //$data['titleEngH1'] = preg_replace($pattern, '', preg_replace($pattern2, '', $data['titleEngH1']));
        //$data['titleFra'] = preg_replace($pattern, '', preg_replace($pattern2, '', $data['titleFra']));
        $data['titleFra'].= (strlen(trim($data['titleFra'])) > 0 ? ' - ':''). 'INFRAnet';
        //$data['titleFraH1'] = preg_replace($pattern, '', preg_replace($pattern2, '', $data['titleFraH1']));
        }
        /**/

        if(isset($data['no-menu'])) $data['menu'] = self::MENU_NO_MENU;

        return $data;
    }

    public function getSubTemplate($uri)
    {
        if(strpos($uri, '/'.$this->getServiceLocator()->get('lang').'/') === 0){
            $uri = str_replace('/'.$this->getServiceLocator()->get('lang').'/', '/', $uri);
        }
        $config = $this->getServiceLocator()->get('Config');
        foreach($config['subtemplate'] as $path=>$subtemplate) {
            if(strpos($uri, $path) === 0) return $subtemplate;
        }
        return '';
    }

    public function getDefaultMenu($uri)
    {
        $config = $this->getServiceLocator()->get('Config');
        foreach($config['default-menu'] as $path=>$menu) {
            if(strpos($uri, $path) === 0) return $menu;
        }
        return '';
    }

    public function toArray()
    {
        return $this->data;
    }
}
