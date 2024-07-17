<?php
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

final class LanguageTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    function test_accept_all_language() {
        # User-Agent Language
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "ar;q=1, cn;q=1, de;q=1, el;q=1, es;q=1, fr;q=1, it;q=1, nb-NO;q=1, pl;q=1, pt-PT;q=1, ru;q=1, sl;q=1, tr;q=1, zh-CN;q=1, ca;q=1, cs;q=1, ee;q=1, en;q=1, eu;q=1, hu;q=1, ja;q=1, nl;q=1, pt-BR;q=1, rs;q=1, sk;q=1, sv;q=1, uk;q=1, zh-TW;q=1";
        
        $availableLanguages = array("ar", "cn", "de", "el", "es", "fr", "it", "nb-NO", "pl", "pt-PT", "ru", "sl", "tr", "zh-CN", "ca", "cs", "ee", "en", "eu", "hu", "ja", "nl", "pt-BR", "rs", "sk", "sv", "uk", "zh-TW");
        $defaultLanguage = "en";

        # Execute function
        $chosenLanguage = \Ltb\Language::detect_language($defaultLanguage, $availableLanguages);

        $this->assertEquals("ar", $chosenLanguage);
    }

    function test_restrict_language() {
        # User-Agent Language
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en";
        
        $availableLanguages = array("ar", "cn", "de", "el", "es", "fr", "it", "nb-NO", "pl", "pt-PT", "ru", "sl", "tr", "zh-CN", "ca", "cs", "ee", "en", "eu", "hu", "ja", "nl", "pt-BR", "rs", "sk", "sv", "uk", "zh-TW");
        $allowedLanguages = array("fr");
        $defaultLanguage = "fr";

        # Execute function
        $chosenLanguage = \Ltb\Language::detect_language($defaultLanguage, $allowedLanguages ? array_intersect($availableLanguages, $allowedLanguages) : $availableLanguages);

        $this->assertEquals("fr", $chosenLanguage);
    }


    function test_default_language() {
        # User-Agent Language
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "ar, en";
        
        $availableLanguages = array("ar", "cn", "de", "el", "es", "it", "nb-NO", "pl", "pt-PT", "ru", "sl", "tr", "zh-CN", "ca", "cs", "ee", "en", "eu", "hu", "ja", "nl", "pt-BR", "rs", "sk", "sv", "uk", "zh-TW");
        $allowedLanguages = array("fr");
        $defaultLanguage = "en";

        # Execute function
        $chosenLanguage = \Ltb\Language::detect_language($defaultLanguage, $allowedLanguages ? array_intersect($availableLanguages, $allowedLanguages) : $availableLanguages);

        $this->assertEquals("en", $chosenLanguage);
    }
}