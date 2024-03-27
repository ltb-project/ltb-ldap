<?php

require __DIR__ . '/../../vendor/autoload.php';

// global variable for ldap_get_mail_for_notification function
$GLOBALS['mail_attributes'] = array("mail");

final class LdapTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{

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

    public function test_sorted_search_with_sort_control(): void
    {

        $phpLDAPMock = Mockery::mock('overload:Ltb\PhpLDAP');

        $phpLDAPMock->shouldreceive('ldap_read')
                    ->with("ldap_connection", '', '(objectClass=*)', ['supportedControl'])
                    ->andReturn("check");

        $phpLDAPMock->shouldreceive('ldap_get_entries')
                    ->andReturnUsing( function ($ldap, $ldap_result)
                                      {
                                          if ($ldap_result == "check") {
                                              return [
                                                         'count' => 1,
                                                         0 => [
                                                             'count' => 1,
                                                             0 => 'supportedcontrol',
                                                             'supportedcontrol' => [
                                                                 'count' => 1,
                                                                 0 => LDAP_CONTROL_SORTREQUEST
                                                             ]
                                                         ]
                                                     ];
                                          }
                                          elseif($ldap_result == "ldap_search_result")
                                          {
                                              return [
                                                         'count' => 2,
                                                         0 => [
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
                                                         ],
                                                         1 => [
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
                                                         ]
                                                     ];

                                          }
                                          else
                                          {
                                              return "ldap_get_entries_error";
                                          }
                                      }
                                    );

        $phpLDAPMock->shouldreceive('ldap_search')
                    ->with("ldap_connection",
                           "ou=people,dc=my-domain,dc=com",
                           "(objectClass=InetOrgPerson)",
                           ["cn", "sn"],
                           0,
                           1000,
                           -1,
                           LDAP_DEREF_NEVER,
                           [['oid' => LDAP_CONTROL_SORTREQUEST, 'value' => [['attr'=>'sn']]]]
                          )
                    ->andReturn("ldap_search_result");

        $phpLDAPMock->shouldreceive('ldap_errno')
                    ->with("ldap_connection")
                    ->andReturn(0);

        list($ldap_result,$errno,$entries) = Ltb\Ldap::sorted_search("ldap_connection",
                                                                     "ou=people,dc=my-domain,dc=com",
                                                                     "(objectClass=InetOrgPerson)",
                                                                     ["cn", "sn"],
                                                                     "sn",
                                                                     1000
                                                                    );

        $this->assertEquals($ldap_result, "ldap_search_result", "error while getting ldap_search sorted result");
        $this->assertEquals($errno, 0, "error code invalid while getting ldap_search sorted result");
        $this->assertEquals($entries[0]['cn'][0], 'testcn2', "error while getting ldap_search sorted result: first entry is not testcn2");
        $this->assertEquals($entries[1]['cn'][0], 'testcn1', "error while getting ldap_search sorted result: second entry is not testcn1");
 
    }

    public function test_sorted_search_without_sort_control(): void
    {

        $phpLDAPMock = Mockery::mock('overload:Ltb\PhpLDAP');

        $phpLDAPMock->shouldreceive('ldap_read')
                    ->with("ldap_connection", '', '(objectClass=*)', ['supportedControl'])
                    ->andReturn("check");

        $phpLDAPMock->shouldreceive('ldap_get_entries')
                    ->andReturnUsing( function ($ldap, $ldap_result)
                                      {
                                          if ($ldap_result == "check") {
                                              return [
                                                         'count' => 1,
                                                         0 => [
                                                             'count' => 1,
                                                             0 => 'supportedcontrol',
                                                             'supportedcontrol' => [
                                                                 'count' => 1,
                                                                 0 => LDAP_CONTROL_VLVREQUEST
                                                             ]
                                                         ]
                                                     ];
                                          }
                                          elseif($ldap_result == "ldap_search_result")
                                          {
                                              return [
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
                                                         ],
                                                     ];

                                          }
                                          else
                                          {
                                              return "ldap_get_entries_error";
                                          }
                                      }
                                    );

        $phpLDAPMock->shouldreceive('ldap_search')
                    ->with("ldap_connection",
                           "ou=people,dc=my-domain,dc=com",
                           "(objectClass=InetOrgPerson)",
                           ["cn", "sn"],
                           0,
                           1000
                          )
                    ->andReturn("ldap_search_result");

        $phpLDAPMock->shouldreceive('ldap_errno')
                    ->with("ldap_connection")
                    ->andReturn(0);

        list($ldap_result,$errno,$entries) = Ltb\Ldap::sorted_search("ldap_connection",
                                                                     "ou=people,dc=my-domain,dc=com",
                                                                     "(objectClass=InetOrgPerson)",
                                                                     ["cn", "sn"],
                                                                     "sn",
                                                                     1000
                                                                    );

        $this->assertEquals($ldap_result, "ldap_search_result", "error while getting ldap_search sorted result");
        $this->assertEquals($errno, 0, "error code invalid while getting ldap_search sorted result");
        $this->assertEquals($entries[0]['cn'][0], 'testcn2', "error while getting ldap_search sorted result: first entry is not testcn2");
        $this->assertEquals($entries[1]['cn'][0], 'testcn1', "error while getting ldap_search sorted result: second entry is not testcn1");

    }
}
