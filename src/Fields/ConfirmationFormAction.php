<?php

use SilverStripe\Dev\Debug;
use SilverStripe\View\SSViewer;
use SilverStripe\Forms\FormAction;

class ConfirmationFormAction extends FormAction
{
    public function Field($properties = array())
    {
        
        $context = $this;

        $properties = array_merge(
            $properties,
            array(
                'Name' => $this->action,
                'Title' => ($this->description && !$this->useButtonTag) ? $this->description : $this->Title(),
                'UseButtonTag' => $this->useButtonTag
            )
        );

        if (count($properties)) {
            $context = $context->customise($properties);
        }
        $result = $context->renderWith("ConfirmationFormAction");
        // Trim whitespace from the result, so that trailing newlines are supressed. Works for strings and HTMLText values
        if (is_string($result)) {
            $result = trim($result);
        } elseif ($result instanceof DBField) {
            $result->setValue(trim($result->getValue()));
        }

        return $result;
    }
}