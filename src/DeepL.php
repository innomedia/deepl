<?php

use SilverStripe\Dev\Debug;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Core\Config\Config;
use SilverStripe\SiteConfig\SiteConfig;

class Deepl
{
    private static $AvailableLanguages = [
        "AR",       // Arabic
        "BG",       // Bulgarian
        "CS",       // Czech
        "DA",       // Danish
        "DE",       // German
        "EL",       // Greek
        "EN",       // English (unspecified variant for backward compatibility)
        "EN-GB",    // English (British)
        "EN-US",    // English (American)
        "ES",       // Spanish
        "ET",       // Estonian
        "FI",       // Finnish
        "FR",       // French
        "HU",       // Hungarian
        "ID",       // Indonesian
        "IT",       // Italian
        "JA",       // Japanese
        "KO",       // Korean
        "LT",       // Lithuanian
        "LV",       // Latvian
        "NB",       // Norwegian Bokmål
        "NL",       // Dutch
        "PL",       // Polish
        "PT",       // Portuguese (unspecified variant for backward compatibility)
        "PT-BR",    // Portuguese (Brazilian)
        "PT-PT",    // Portuguese (all Portuguese variants excluding Brazilian Portuguese)
        "RO",       // Romanian
        "RU",       // Russian
        "SK",       // Slovak
        "SL",       // Slovenian
        "SV",       // Swedish
        "TR",       // Turkish
        "UK",       // Ukrainian
        "ZH",       // Chinese (unspecified variant for backward compatibility)
        "ZH-HANS",  // Chinese (simplified)
        "ZH-HANT"   // Chinese (traditional)
    ];

    public static function maskText($text)
    {
        $tmptext = str_replace('<span class="\">', "<br class='spanopen'/>", $text);
        $tmptext = str_replace('</span>', "<br class='spanclose'/>", $tmptext);
        return $tmptext;
    }
    public static function unMaskText($text)
    {
        $tmptext = str_replace("<br class='spanopen'/>", '<span class="\">', $text);
        $tmptext = str_replace("<br class='spanclose'/>", '</span>', $tmptext);
        return $tmptext;
    }
    private static function is_html($string)
    {
        return preg_match("/<[^<]+>/", $string, $m) != 0;
    }
    private static function updateLanguageString($string)
    {        
        switch($string)
        {
            case "zh-CN":
            case "ZH-CN":
                $string = "zh-HANT";
                break;
        }

        if (in_array(strtoupper($string), self::$AvailableLanguages)) {
            return $string;
        }
    
        // Split the string by "-" or "_"
        $parts = preg_split('/[-_]/', $string);
    
        // Find the first valid part in AvailableLanguages
        foreach ($parts as $part) {
            if (in_array(strtoupper($part), self::$AvailableLanguages)) {
                $string = strtoupper($part);
                return $string;
            }
        }

        return $string;
    }
    public static function TranslateString($text, $targetlang, $sourcelang = null)
    {
        $targetlang = self::updateLanguageString($targetlang);
        $apikey = Config::inst()->get('DeepL', 'APIKEY');
        if ($apikey != null) {

            $texts = [];
            $parsed = false;
            if (class_exists("KubAT\PhpSimple\HtmlDomParser") && Deepl::is_html($text)) {
                $parsed = true;
                $tmptext = "<body>$text</body>";
                $root = KubAT\PhpSimple\HtmlDomParser::str_get_html($tmptext);
                foreach ($root->nodes as $childnode) {
                    if ($childnode->tag == "text") {
                        $content = $childnode->__toString();
                        if (trim($content) != "") {
                            $texts[] = $content;
                        }
                    }
                }
            } else {
                $tmptext = Deepl::maskText($text);
                $texts[] = $tmptext;
            }
            $results = Deepl::TranslationRequest($apikey, $targetlang, $texts, $sourcelang, $parsed);
            if ($results != null && array_key_exists("translations", $results) && count($results["translations"]) == 1) {
                return $results["translations"][0]["text"];
            }
            if ($results != null && array_key_exists("translations", $results) && count($results["translations"]) > 1 && class_exists("KubAT\PhpSimple\HtmlDomParser")) {
                $tmptext = "<body>$text</body>";
                $root = KubAT\PhpSimple\HtmlDomParser::str_get_html($tmptext);
                $index = 0;
                foreach ($root->nodes as $childnode) {
                    if ($childnode->tag == "text") {
                        $content = $childnode->__toString();
                        if (trim($content) != "") {
                            $childnode->innertext = $results["translations"][$index]["text"];
                            $index++;
                        }
                    }
                }
                return $root->innertext;
            }
        }
        return $text;
    }
    private static function TranslationRequest($apikey, $targetlang, array $texts, $sourcelang = null, $parsed = false)
    {
        $translator = new \DeepL\Translator($apikey);
        $results = [];
        $translated = false;
        foreach ($texts as $text) {
            $result = $translator->translateText($text,$sourcelang,$targetlang,["tag_handling" => "html"]);
            if (!$parsed) {
                $results[] = [
                    "detected_source_language" => $result->detectedSourceLang,
                    "text" => Deepl::unMaskText($result->text)
                ];
                $translated = true;
            }
            else
            {
                $results[] = [
                    "detected_source_language" => strtoupper($result->detectedSourceLang),
                    "text" => $result->text
                ];
                $translated = true;
            }
        }
        $data = [
            "translations" => $results
        ];
        try
        {
            $siteconfig = SiteConfig::current_site_config();
            if($siteconfig->hasExtension("DeeplSiteConfigExtension"))
            {
                $count = 0;
                foreach ($texts as $text) {
                    $count += strlen($text);
                }
                $siteconfig->addTranslatedCharCount($count);
                $siteconfig->write();
            }
        }catch(Exception $ex)
        {

        }
        try
        {
            if (count($data["translations"]) > 0) {
                return $data;
            }
            return null;
        } catch (Exception $ex) {

        }
    }

    public static function cleanString($string)
    {
        return str_replace("li>", "li> ", $string);
    }
    public static function canTranslate($lang)
    {
        $apikey = Config::inst()->get('DeepL', 'APIKEY');
        if ($apikey == null || $apikey == "") {
            return false;
        }
        if ($lang == strtoupper(explode("_", Locale::getDefault()->Locale)[0])) {
            return false;
        }
        return in_array(strtoupper($lang), Deepl::$AvailableLanguages);
    }
}

