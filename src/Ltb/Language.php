<?php namespace Ltb;
/**
 * detect the preferred language of the user agent
 *
 * @copyright Roy Kaldung <roy@kaldung.com>
 * @license http://www.php.net/license/3_01.txt PHP license
 */

final class Language {

    /**
     * split request header Accept-Language to determine the UserAgent's
     * prefered language
     *
     * @param string $defaultLanguage preselected default language
     * @return string returns the default language or a match from $availableLanguages
     */
    static function detect_language($defaultLanguage, $availableLanguages): string
    {
        $acceptedLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ? htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE']) : "";
        $languageList      = explode(',', $acceptedLanguages);
        $choosenLanguage= $defaultLanguage;
        foreach($languageList as $currentLanguage) {
            $currentLanguage = explode(';', $currentLanguage);
            if (preg_match('/(..)-?.*/', $currentLanguage[0], $reg)) {
                foreach($reg as $checkLang) {
                    if ($match = preg_grep('/'.$checkLang.'/i', $availableLanguages)) {
                        $choosenLanguage= $match[key($match)];
                        break 2;
                    }
                }
            }
        }
        return $choosenLanguage;
    }
}