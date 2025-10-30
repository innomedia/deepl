<?php

use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;

class DeeplSiteConfigExtension extends Extension
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