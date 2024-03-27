<?php

require __DIR__ . '/../../vendor/autoload.php';
use PHPUnit\Framework\TestCase;

// global variable for ldap_get_mail_for_notification function
$GLOBALS['mail_attributes'] = array("mail");

final class LdapTest extends TestCase
{

    protected function tearDown(): void
    {
        // Useful for destroying the mock between two tests
        Mockery::close();
    }

    public function test_connect(): void
    {

        $phpLDAPMock = Mockery::mock('overload:Ltb\PhpLDAP');

        $phpLDAPMock->shouldreceive('ldap_connect')
                    ->with("ldap://test.my-domain.com")
                    ->andReturn("ldap_connection");

        $phpLDAPMock->shouldreceive('ldap_set_option')
                    ->andReturn(null);

        $phpLDAPMock->shouldreceive('ldap_bind')
                    ->with("ldap_connection", "cn=test,dc=my-domain,dc=com","secret")
                    ->andReturn(true);

        list($ldap, $msg) = Ltb\Ldap::connect("ldap://test.my-domain.com", false, "cn=test,dc=my-domain,dc=com", "secret", 10, null);

        $this->assertNotFalse($ldap, "Error while connecting to LDAP server");
        $this->assertFalse($msg, "Error message returned while connecting to LDAP server");
    }

    public function test_get_list(): void
    {

        $phpLDAPMock = Mockery::mock('overload:Ltb\PhpLDAP');

        $phpLDAPMock->shouldreceive('ldap_search')
                    ->with("ldap_connection", "ou=people,dc=my-domain,dc=com", "(uid=test)", array("cn", "sn"))
                    ->andReturn("ldap_search_result");

        $phpLDAPMock->shouldreceive('ldap_errno')
                    ->with("ldap_connection")
                    ->andReturn(false);

        $phpLDAPMock->shouldreceive('ldap_get_entries')
                    ->with("ldap_connection","ldap_search_result")
                    ->andReturn([
                                    'count' => 2,
                                    0 => [
                                        'count' => 2,
                                        0 => 'cn',
                                        1 => 'sn',
                                        'cn' => [
                                            'count' => 1,
                                            0 => 'testcn1'
                                        ],
                                        'sn' => [
                                            'count' => 1,
                                            0 => 'testsn1'
                                        ]
                                    ],
                                    1 => [
                                        'count' => 2,
                                        0 => 'cn',
                                        1 => 'sn',
                                        'cn' => [
                                            'count' => 1,
                                            0 => 'testcn2'
                                        ],
                                        'sn' => [
                                            'count' => 1,
                                            0 => 'testsn2'
                                        ]
                                    ]
                                ]);

        // return hashmap: [ cn_value => sn_value ]
        $result = Ltb\Ldap::get_list("ldap_connection", "ou=people,dc=my-domain,dc=com", "(uid=test)", "cn","sn");

        $this->assertEquals(array_keys($result)[0], 'testcn1', "not getting testcn1 as key in get_list function");
        $this->assertEquals($result["testcn1"], 'testsn1', "not getting testsn1 as value in get_list function");

        $this->assertEquals(array_keys($result)[1], 'testcn2', "not getting testcn2 as key in get_list function");
        $this->assertEquals($result["testcn2"], 'testsn2', "not getting testsn2 as value in get_list function");

    }

    public function test_ldapSort(): void
    {

        $entries = [
            'count' => 2,
            0 => [
                'count' => 2,
                0 => 'cn',
                1 => 'sn',
                'cn' => [
                    'count' => 1,
                    0 => 'testcn1'
                ],
                'sn' => [
                    'count' => 1,
                    0 => 'zzzz'
                ]
            ],
            1 => [
                'count' => 2,
                0 => 'cn',
                1 => 'sn',
                'cn' => [
                    'count' => 1,
                    0 => 'testcn2'
                ],
                'sn' => [
                    'count' => 1,
                    0 => 'aaaa'
                ]
            ]
        ];

        $return = Ltb\Ldap::ldapSort($entries, "sn");

        $this->assertTrue($return, "Weird value returned by ldapSort function");
        $this->assertEquals($entries[0]['cn'][0], 'testcn2', "testcn2 has not been ordered correctly in entries array");
        $this->assertEquals($entries[1]['cn'][0], 'testcn1', "testcn1 has not been ordered correctly in entries array");
    }


}
