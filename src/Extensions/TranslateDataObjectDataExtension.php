<?php

use SilverStripe\Dev\Debug;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Forms\CheckboxField;

class TranslateDataObjectDataExtension extends DataExtension
{

    private static $fixed_fields = [
        "DeepLTranslated" => "Boolean(0)"
    ];
}