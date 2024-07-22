<?php
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

final class LanguageTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    function test_accept_all_language() {
        # User-Agent Language
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en";
        
        $availableLanguages = array("ar","cn","de","el","es","fr","it","nb-NO","pl","pt-PT","ru","sl","tr","zh-CN","ca","cs","ee","en","eu","hu","ja","nl","pt-BR","rs","sk","sv","uk","zh-TW");
        $defaultLanguage = "en";

        # Execute function
        $chosenLanguage = \Ltb\Language::detect_language($defaultLanguage, $availableLanguages);

        $this->assertEquals("en", $chosenLanguage);
    }

    function test_restrict_language() {
        # User-Agent Language
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en";
        
        $availableLanguages = array("ar","cn","de","el","es","fr","it","nb-NO","pl","pt-PT","ru","sl","tr","zh-CN","ca","cs","ee","en","eu","hu","ja","nl","pt-BR","rs","sk","sv","uk","zh-TW");
        $allowedLanguages = array("fr");
        $defaultLanguage = "fr";

        # Execute function
        $chosenLanguage = \Ltb\Language::detect_language($defaultLanguage, $allowedLanguages ? array_intersect($availableLanguages, $allowedLanguages) : $availableLanguages);

        $this->assertEquals("fr", $chosenLanguage);
    }


    function test_default_language() {
        # User-Agent Language
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "ar, en";
        
        $availableLanguages = array("ar","cn","de","el","es","fr","it","nb-NO","pl","pt-PT","ru","sl","tr","zh-CN","ca","cs","ee","en","eu","hu","ja","nl","pt-BR","rs","sk","sv","uk","zh-TW");
        $allowedLanguages = array("fr");
        $defaultLanguage = "en";

        # Execute function
        $chosenLanguage = \Ltb\Language::detect_language($defaultLanguage, $allowedLanguages ? array_intersect($availableLanguages, $allowedLanguages) : $availableLanguages);

        $this->assertEquals("en", $chosenLanguage);
    }
}