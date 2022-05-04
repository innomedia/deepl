<?php

use SilverStripe\Core\Config\Config;
use TractorCow\Fluent\Model\Locale;

class Deepl
{
    private static $AvailableLanguages = ["EN", "DE", "FR", "ES", "PT", "IT", "NL", "PL", "RU"];

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
    public static function TranslateString($text, $targetlang, $sourcelang = null)
    {
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
        $urls = [];
        $url = "https://api.deepl.com/v2/translate";
        $url .= "?auth_key=" . $apikey;
        foreach ($texts as $text) {
            $url .= "&text=" . rawurlencode($text);
        }

        $url .= "&target_lang=" . $targetlang;
        $url .= "&tag_handling=xml";
        if ($sourcelang != null) {
            $url .= "&source_lang=" . $sourcelang;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $url);
        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$parsed) {
            $response = Deepl::unMaskText($response);
        }

        if ($httpCode === 403) {
            throw new Exception("Authentication Failed");
        }
        if ($httpCode !== 200) {
            return $text;
        }
        $data = json_decode($response, true);
        try
        {
            if (json_decode($response)->translations && count(json_decode($response)->translations) > 0) {
                return $data;
            }
            return null;
        } catch (Exception $ex) {

        }
        try
        {
            $count = 0;
            foreach ($texts as $text) {
                $count += strlen($text);
            }
            $siteconfig = SiteConfig::current_site_config();
            if(method_exists($siteconfig,"addTranslatedCharCount"))
            {
                $siteconfig->addTranslatedCharCount($count);
                $siteconfig->write();
            }
        }catch(Exception $ex)
        {

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

