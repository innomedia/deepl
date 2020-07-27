<?php

use SilverStripe\Dev\Debug;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;

class TranslateDataObjectExtension extends DataExtension
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
                $tmp = explode('_', Locale::getCurrentLocale()->Locale);
                $targetlocale = strtoupper($tmp[0]);
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
                foreach ($localisedFields as $field) {
                    if ($field != "" && $this->owner->getField($field) != "") {
                        $translation = Deepl::TranslateString($this->owner->getField($field), $targetlocale,$sourcelocale);
                        $this->owner->setField($field, $translation);
                    }
                }
                $this->owner->DeepLTranslated = true;
            }
        }
    }
}
