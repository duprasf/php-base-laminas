<?php

namespace Stockpile\Model;

use Application\Model\Metadata;
use Void\ArrayObject;

/**
* This class replace the old Health Canada's template. Is it meant to accelerate
* the convertion of pages that used this old template to the new one.
*
* exemple:
* $page->setTitle(get_string('rhs_project_update', $language_id));
* $page->setDesc(get_string('general_capc_desc', $language_id));
* $page->setDateCreated("2016-04-29");
*
* Should be changed (with search/replace) to this:
* $this->page->setTitle(get_string('rhs_project_update', $language_id));
* $this->page->setDesc(get_string('general_capc_desc', $language_id));
* $this->page->setDateCreated("2016-04-29");
*/
class OldHealthCanadaMetadata extends Metadata
{
    protected $map = [
        "setTitle" => "title",
        "setDesc" => "description",
        "setDateCreated" => "issued",
        "setDateModified" => "modified",
        "setDateMeta" => "keywords",
        "setDcSubjects" => "subject",
        "setDcCreator" => "creator",
        "setLangUrl" => "switch-lang-url",
        "setAttribution"=>"attribution",
        "setPageTag"=>"pagetag",
        "setThumbnail"=>"thumbnail",
    ];

    public function __call($name, $args)
    {
        if(isset($this->map[$name])) {
            $this[$this->map[$name]] = $args[0];
        }
        if($name == "showNoRobots") {
            $this["noRobots"]=true;
        }
    }
}
