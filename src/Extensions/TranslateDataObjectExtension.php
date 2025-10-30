<?php

use SilverStripe\Dev\Debug;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use TractorCow\Fluent\State\FluentState;
use SilverStripe\Core\Config\Config;

class TranslateDataObjectExtension extends Extension
{
    public function updateCMSFields(FieldList $fields)
    {
        $tmp = explode('_', Locale::getCurrentLocale()->Locale);
        $lang = strtoupper($tmp[0]);
        if (Deepl::canTranslate($lang)) {
            
            $fields->push(DropdownField::create('OriginalLanguage','Orginalsprache (von der übersetzt wird)',$this->getLocaleArray()));
            $fields->push(CheckboxField::create("Translate", "Übersetzen?"));
        }
    }
    private function getLocaleArray()
    {
        $array = [];
        foreach(Locale::getLocales() as $locale)
        {
            if($locale->Locale != Locale::getCurrentLocale()->Locale)
            {
                $lang = strtoupper(substr($locale->Locale,0,2));
                $array[$lang] = $locale->Title;
            }
        }
        return $array;
    }
    public function onBeforeWrite()
    {
        if (array_key_exists("Translate", $this->owner->getChangedFields(false, 1))) {
            if ($this->owner->getChangedFields(false, 1)["Translate"]["after"] == 1) {
                $sourcelocale = $this->owner->getChangedFields(false, 1)["OriginalLanguage"]["after"];
                $targetlocale = str_replace("_","-",Locale::getCurrentLocale()->Locale);
            
                $targetlocale = strtoupper($targetlocale);
                $localisedFields = [];
                $includedTables = [];
                $baseClass = $this->owner->baseClass();
                $tableClasses = ClassInfo::ancestry($this->owner, true);

                foreach ($tableClasses as $class) {
                    // Check translated fields for this class (except base table, which is always scaffolded)
                    $translatedFields = $this->owner->getLocalisedFields($class);
                    foreach ($translatedFields as $key => $value) {
                        if (strtolower($value) != "int" && strtolower($value) != "float" && $key != "SiteTreeLinkerHelper" && $key != "URLSegment" && $key != "ReportClass" && $key != "ImageCropping") {
                            array_push($localisedFields, $key);
                        }
                    }
                }
                if($configuredSourceLocale = Config::inst()->get('DeepL', 'translateFromSourceLocale'))
                {
                    $fieldValuesSourceLocale = FluentState::singleton()->withState(function(FluentState $state) use ($configuredSourceLocale, $localisedFields) {
                        $state->setLocale($configuredSourceLocale);
                        $fieldValues = [];
                        $object = $this->owner->ClassName::get()->byID($this->owner->ID);
                        foreach ($localisedFields as $field) {
                            if ($field != "" && $object->getField($field) != "") {
                                $fieldValues[$field] = $object->getField($field);
                            }
                        }
                        return $fieldValues;
                    });


                    foreach ($fieldValuesSourceLocale as $fieldName => $fieldValue){
                        $translation = Deepl::TranslateString($fieldValue, $targetlocale,$sourcelocale);
                        $this->owner->setField($fieldName, $translation);
                    }
                } else {
                    foreach ($localisedFields as $field) {
                        if ($field != "" && $this->owner->getField($field) != "") {
                            $translation = Deepl::TranslateString($this->owner->getField($field), $targetlocale,$sourcelocale);
                            $this->owner->setField($field, $translation);
                        }
                    }
                }
                $this->owner->DeepLTranslated = true;
            }
        }
    }
}
