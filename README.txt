Add this to Page

public function getCMSActions()
    {
        $fields = parent::getCMSActions();
        $tmp = explode('_', $this->Locale);
        $lang = strtoupper($tmp[0]);
        if (Deepl::canTranslate($lang))
        {
            $fields->fieldByName("MajorActions")->push(
                ConfirmationFormAction::create("doTranslate","Übersetzen")->addExtraClass("btn-primary")
            );
        }
        return $fields;
    }