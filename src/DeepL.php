<?php

use SilverStripe\Dev\Debug;
use SilverStripe\i18n\i18n;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Core\Config\Config;

class Deepl
{
    private static $AvailableLanguages = ["EN","DE","FR","ES","PT","IT","NL","PL","RU"];

    public static function TranslateString($text,$targetlang,$sourcelang = null)
    {
        $apikey = Config::inst()->get('DeepL', 'APIKEY');
        if($apikey != null)
        {
            $url = "https://api.deepl.com/v2/translate";
            $url .= "?auth_key=".$apikey;
            $url .= "&text=".rawurlencode($text);
            $url .= "&target_lang=".$targetlang;
            $url .= "&tag_handling=xml";
            if($sourcelang!=null)
            {
                $url .= "&source_lang=".$sourcelang;
            }
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, $url);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch); 
            //return json_decode($response)["text"];
            $data = json_decode($response);
            try
            {
                if(json_decode($response)->translations && count(json_decode($response)->translations)>0)
                {
                    if($data->translations[0]->detected_source_language != $targetlang)
                    {
                        return $data->translations[0]->text;
                    }
                    else
                    {
                        return $text;
                    }
                }
            }
            catch(Exception $ex)
            {

            }
        }
        
    }
    public static function canTranslate($lang)
    {
        if($lang == strtoupper(explode("_",Locale::getDefault()->Locale)[0]))
        {
            return false;
        }
        return in_array(strtoupper($lang),Deepl::$AvailableLanguages);
    }
}