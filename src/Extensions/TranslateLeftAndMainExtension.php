<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\Form;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Core\Extension;
use TractorCow\Fluent\State\FluentState;


class TranslateLeftAndMainExtension extends Extension
{
    private static $allowed_actions = [
        "doTranslate"
    ];
    public function doTranslate($data,$params)
    {
        $obj = $data["ClassName"]::get()->byID($data["ID"]);
        if($obj->DeepLTranslated == false)
        {
            $targetlocale = str_replace("_","-",$obj->Locale);
            $localisedFields = [];
            $includedTables = [];
            $baseClass = $obj->baseClass();
            $tableClasses = ClassInfo::ancestry($obj, true);
            foreach ($tableClasses as $class) {
                // Check translated fields for this class (except base table, which is always scaffolded)
                $translatedFields = $obj->getLocalisedFields($class);
                foreach($translatedFields as $key => $value)
                {
                    if(strtolower($value) != "int" && strtolower($value) != "float" && $key != "SiteTreeLinkerHelper" && $key != "URLSegment" && $key != "ReportClass" && $key != "ImageCropping")
                    {
                        array_push($localisedFields,$key);
                    }
                }
            }
            if($configuredSourceLocale = Config::inst()->get('DeepL', 'translateFromSourceLocale'))
            {
                $fieldValuesSourceLocale = FluentState::singleton()->withState(function(FluentState $state) use ($configuredSourceLocale, $localisedFields, $obj) {
                    $state->setLocale($configuredSourceLocale);
                    $fieldValues = [];
                    $object = $obj->ClassName::get()->byID($obj->ID);
                    foreach ($localisedFields as $field) {
                        if ($field != "" && $object->getField($field) != "") {
                            $fieldValues[$field] = $object->getField($field);
                        }
                    }
                    return $fieldValues;
                });

                foreach ($fieldValuesSourceLocale as $fieldName => $fieldValue){
                    $translation = Deepl::TranslateString($fieldValue, $targetlocale);
                    $obj->setField($fieldName, $translation);
                }
            } else {
                foreach($localisedFields as $field)
                {
                    if($field != "" && $obj->getField($field) != "")
                    {
                        $translation = Deepl::TranslateString($obj->getField($field),$targetlocale);
                        $obj->setField($field,$translation);
                    }
                }
            }
            $obj->DeepLTranslated = true;
            $obj->write();
            $this->owner->response->addHeader(
                'X-Status',
                rawurlencode('Erfolgreich Übersetzt!') 
            );
            return $this->owner->getResponseNegotiator()
                   ->respond($this->owner->request);
        }
        
        $this->owner->response->addHeader(
            'X-Status',
            rawurlencode('Wurde schon Übersetzt!') 
        );
        return $this->owner->getResponseNegotiator()
               ->respond($this->owner->request);
    }
}
