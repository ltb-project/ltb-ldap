<?php

require __DIR__ . '/../../vendor/autoload.php';
use PHPUnit\Framework\TestCase;

// global variable for ldap_get_mail_for_notification function
$GLOBALS['mail_attributes'] = array("mail");

final class AttributeValueTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{

    public function test_ldap_get_first_available_value(): void
    {

        $phpLDAPMock = Mockery::mock('overload:Ltb\PhpLDAP');
        $phpLDAPMock->shouldreceive([
                                      'ldap_get_attributes' => ['cn'],
                                      'ldap_get_values' => [
                                                             'count' => 3,
                                                             0 => 'test1',
                                                             1 => 'test2',
                                                             2 => 'test3'
                                                           ]
                                    ]);

        $ent = Ltb\AttributeValue::ldap_get_first_available_value(null, null, ['cn']);
        $this->assertEquals($ent->attribute, "cn", "not getting attribute cn");
        $this->assertEquals($ent->value, "test1", "not getting value test1 as cn first value");
        
    }

    public function test_ldap_get_mail_for_notification(): void
    {

        $phpLDAPMock = Mockery::mock('overload:Ltb\PhpLDAP');
        $phpLDAPMock->shouldreceive([
                                      'ldap_get_attributes' => ['mail'],
                                      'ldap_get_values' => [
                                                             'count' => 2,
                                                             0 => 'test1@domain.com',
                                                             1 => 'test2@domain.com'
                                                           ]
                                    ]);

        # Test ldap_get_first_available_value
        $mail = Ltb\AttributeValue::ldap_get_mail_for_notification(null, null);
        $this->assertEquals($mail, 'test1@domain.com', "not getting test1@domain.com as mail for notification");
    }

}
