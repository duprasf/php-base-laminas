<?php

namespace Application\Model;

use ArrayObject;
use Laminas\Mvc\I18n\Translator;
use Application\Exception\MetadataException;
use Application\Model\Breadcrumbs;

/**
* NEW VERSION of MetadataBuilder
* Build the metadata and adds the default values if any or missing
*/
class Metadata extends ArrayObject
{
    private $ready = false;

    protected $defaultMetadata = [];
    public function setDefaultMetadata(array $data)
    {
        $this->defaultMetadata = $data;
        return $this;
    }
    public function getDefaultMetadata()
    {
        return $this->defaultMetadata;
    }

    public function getDefaultMetadataFromEnvVariables()
    {
        $a = [
            'issued' => getenv('ISSUED_DATE'),
            'modified' => getenv('LAST_MODIFIED_DATE'),
        ];
        // this is to remove keys with "false" which is the default return from getenv()
        return array_filter($a);
    }

    public function __construct(array|ArrayObject|null $data = [])
    {
        $this->exchangeArray($data ?? []);
        $this->init();
    }

    public function init()
    {
        $this->exchangeArray(array_merge(
            $this->getDefaultMetadata(),
            $this->getDefaultMetadataFromEnvVariables(),
            $this->getArrayCopy()
        ));
        return $this;
    }

    public function merge(array|ArrayObject|null $data = null)
    {
        if($data == null) {
            return $this;
        }

        if(!is_array($data)) {
            $data = $data->getArrayCopy();
        }

        $this->exchangeArray(array_merge(
            $this->getArrayCopy(),
            $data,
        ));
        return $this;
    }

    public function completeMetadata(array|ArrayObject|null $metadata = [])
    {
        $error = array();
        $this->merge($metadata);

        if(count($this) == 0) {
            throw new MetadataException('no metadata found');
        }

        // name changed, to be backward compatible
        if(isset($this['issuedDate']) && !isset($this['issued'])) {
            $this['issued'] = $this['issuedDate'];
        }
        if(isset($this['modifiedDate']) && !isset($this['modifiedDate'])) {
            $this['modified'] = $this['modifiedDate'];
        }


        if(!isset($this['title'])) {
            throw new MetadataException('Title is required');
        }
        if(!isset($this['description'])) {
            throw new MetadataException('Description is required');
        }
        if(!isset($this['issued']) || !preg_match('(^(\d{4}-\d{2}-\d{2})(?: \d{1,2}:\d{1,2}(?::\d{1,2})?)?$)', $this['issued'], $parsedIssuedDate)) {
            throw new MetadataException('Issued is required (issued date)');
        }

        if(!isset($this['titleH1']) && isset($this['title'])) {
            $this['titleH1'] = $this['title'];
        }

        $this['issuedTimestamp'] = strtotime($this['issued']);
        $this['issued'] = $parsedIssuedDate[1];

        if(isset($this['modified']) && preg_match('(^(\d{4}-\d{2}-\d{2})(?: \d{1,2}:\d{1,2}(?::\d{1,2})?)?$)', $this['issued'], $parsedModifiedDate)) {
            $this['modifiedTimestamp'] = strtotime($this['modified']);
            $this['modified'] = $parsedModifiedDate[1];
        } else {
            $this['modifiedTimestamp'] = $this['issuedTimestamp'];
            $this['modified'] = $this['issued'];
        }

        if($this['issuedTimestamp'] > $this['modifiedTimestamp']) {
            throw new MetadataException('Modified date is prior to the issued date');
        }

        if(!isset($this['breadcrumbs'])) {
            $this['breadcrumbs'] = new Breadcrumbs([]);
        }
        if(is_array($this['breadcrumbs'])) {
            $this['breadcrumbs'] = new Breadcrumbs($this['breadcrumbs']);
        }

        if(isset($this['extra-css'])) {
            $extra = array();
            if(is_array($this['extra-css'])) {
                foreach($this['extra-css'] as $val) {
                    if(preg_match_all('(([^\|]*)(?:\s*\|\s*)?)', $val, $out)) {
                        foreach($out[1] as $item) {
                            if(trim($item)) {
                                $extra[] = $item;
                            }
                        }
                    }
                }
            } else {
                if(preg_match_all('(([^\|]*)(?:\s*\|\s*)?)', $this['extra-css'], $out)) {
                    foreach($out[1] as $item) {
                        if(trim($item)) {
                            $extra[] = $item;
                        }
                    }
                }
            }
            $this['extra-css'] = $extra;
        }
        if(isset($this['extra-js'])) {
            $extra = array();
            if(is_array($this['extra-js'])) {
                foreach($this['extra-js'] as $val) {
                    if(preg_match_all('(([^\|]*)(?:\s*\|\s*)?)', $val, $out)) {
                        foreach($out[1] as $item) {
                            if(trim($item)) {
                                $extra[] = $item;
                            }
                        }
                    }
                }
            } else {
                if(preg_match_all('(([^\|]*)(?:\s*\|\s*)?)', $this['extra-js'], $out)) {
                    foreach($out[1] as $item) {
                        if(trim($item)) {
                            $extra[] = $item;
                        }
                    }
                }
            }
            $this['extra-js'] = $extra;
        }

        if(isset($this['contactLinks'])) {
            if(!is_array($this['contactLinks'])) {
                $this['contactLinks'] = [$this['contactLinks']];
            }
            $contact = [];
            foreach($this['contactLinks'] as $key => $val) {
                if(is_numeric($key)) {
                    $key = 'href';
                }
                $contact[] = [$key => $val];
            }
            $this['contactLinks'] = json_encode($contact);
        }

        return $this;
    }
}
