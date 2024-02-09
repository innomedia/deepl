<?php
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
class DeeplSiteConfigExtension extends DataExtension
{
    private static $db = [
        "TranslatedCharCount"   =>  'Int'
    ];
    public function addTranslatedCharCount($add = 0)
    {
        if($add <= 0 || !is_numeric($add))
        {
            return;
        }
        $this->owner->TranslatedCharCount += $add;

    }
}