<?php

use SilverStripe\Dev\Debug;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Forms\CheckboxField;

class TranslateDataObjectDataExtension extends Extension
{

    private static $fixed_fields = [
        "DeepLTranslated" => "Boolean(0)"
    ];
}