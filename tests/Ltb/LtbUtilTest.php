<?php

require __DIR__ . '/../../vendor/autoload.php';

// global variable for ldap_get_mail_for_notification function
$GLOBALS['mail_attributes'] = array("mail");
$GLOBALS['ldap_url'] = "ldap://test.my-domain.com";
$GLOBALS['ldap_starttls'] = false;
$GLOBALS['ldap_binddn'] = "cn=test,dc=my-domain,dc=com";
$GLOBALS['ldap_bindpw'] = "secret";
$GLOBALS['ldap_network_timeout'] = 10;
$GLOBALS['ldap_user_base'] = "ou=people,dc=my-domain,dc=com";
$GLOBALS['ldap_size_limit'] = 1000;
$GLOBALS['attributes_map'] = array(
    'authtimestamp' => array( 'attribute' => 'authtimestamp', 'faclass' => 'lock', 'type' => 'date' ),
    'businesscategory' => array( 'attribute' => 'businesscategory', 'faclass' => 'briefcase', 'type' => 'text' ),
    'carlicense' => array( 'attribute' => 'carlicense', 'faclass' => 'car', 'type' => 'text' ),
    'created' => array( 'attribute' => 'createtimestamp', 'faclass' => 'clock-o', 'type' => 'date' ),
    'description' => array( 'attribute' => 'description', 'faclass' => 'info-circle', 'type' => 'text' ),
    'displayname' => array( 'attribute' => 'displayname', 'faclass' => 'user-circle', 'type' => 'text' ),
    'employeenumber' => array( 'attribute' => 'employeenumber', 'faclass' => 'hashtag', 'type' => 'text' ),
    'employeetype' => array( 'attribute' => 'employeetype', 'faclass' => 'id-badge', 'type' => 'text' ),
    'fax' => array( 'attribute' => 'facsimiletelephonenumber', 'faclass' => 'fax', 'type' => 'tel' ),
    'firstname' => array( 'attribute' => 'givenname', 'faclass' => 'user-o', 'type' => 'text' ),
    'fullname' => array( 'attribute' => 'cn', 'faclass' => 'user-circle', 'type' => 'text' ),
    'identifier' => array( 'attribute' => 'uid', 'faclass' => 'user-o', 'type' => 'text' ),
    'l' => array( 'attribute' => 'l', 'faclass' => 'globe', 'type' => 'text' ),
    'lastname' => array( 'attribute' => 'sn', 'faclass' => 'user-o', 'type' => 'text' ),
    'mail' => array( 'attribute' => 'mail', 'faclass' => 'envelope-o', 'type' => 'mailto' ),
    'mailquota' => array( 'attribute' => 'gosamailquota', 'faclass' => 'pie-chart', 'type' => 'bytes' ),
    'manager' => array( 'attribute' => 'manager', 'faclass' => 'user-circle-o', 'type' => 'dn_link' ),
    'mobile' => array( 'attribute' => 'mobile', 'faclass' => 'mobile', 'type' => 'tel' ),
    'modified' => array( 'attribute' => 'modifytimestamp', 'faclass' => 'clock-o', 'type' => 'date' ),
    'organization' => array( 'attribute' => 'o', 'faclass' => 'building', 'type' => 'text' ),
    'organizationalunit' => array( 'attribute' => 'ou', 'faclass' => 'building-o', 'type' => 'text' ),
    'pager' => array( 'attribute' => 'pager', 'faclass' => 'mobile', 'type' => 'tel' ),
    'phone' => array( 'attribute' => 'telephonenumber', 'faclass' => 'phone', 'type' => 'tel' ),
    'postaladdress' => array( 'attribute' => 'postaladdress', 'faclass' => 'map-marker', 'type' => 'address' ),
    'postalcode' => array( 'attribute' => 'postalcode', 'faclass' => 'globe', 'type' => 'text' ),
    'pwdaccountlockedtime' => array( 'attribute' => 'pwdaccountlockedtime', 'faclass' => 'lock', 'type' => 'date' ),
    'pwdchangedtime' => array( 'attribute' => 'pwdchangedtime', 'faclass' => 'lock', 'type' => 'date' ),
    'pwdfailuretime' => array( 'attribute' => 'pwdfailuretime', 'faclass' => 'lock', 'type' => 'date' ),
    'pwdlastsuccess' => array( 'attribute' => 'pwdlastsuccess', 'faclass' => 'lock', 'type' => 'date' ),
    'pwdreset' => array( 'attribute' => 'pwdreset', 'faclass' => 'lock', 'type' => 'boolean' ),
    'secretary' => array( 'attribute' => 'secretary', 'faclass' => 'user-circle-o', 'type' => 'dn_link' ),
    'state' => array( 'attribute' => 'st', 'faclass' => 'globe', 'type' => 'text' ),
    'street' => array( 'attribute' => 'street', 'faclass' => 'map-marker', 'type' => 'text' ),
    'title' => array( 'attribute' => 'title', 'faclass' => 'certificate', 'type' => 'text' ),
);
$GLOBALS['search_result_title'] = "fullname";
$GLOBALS['search_result_sortby'] = "lastname";
$GLOBALS['search_result_items'] = array('identifier', 'mail', 'mobile');

final class LtbUtilTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{

// For avoiding error:
// Mockery\Exception\RuntimeException: Could not load mock \Ltb\Ldap, class already exists
/**
 * @runInSeparateProcess
 */
#[RunInSeparateProcess]
    public function test_search(): void
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
                               0 => 'zzzzzz'
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
                               0 => 'aaaaaa'
                           ]
                       ]
        ];


        $ltbLDAPMock = Mockery::mock('overload:\Ltb\Ldap');

        $ltbLDAPMock->shouldreceive('connect')
                    ->with($GLOBALS['ldap_url'],
                           $GLOBALS['ldap_starttls'],
                           $GLOBALS['ldap_binddn'],
                           $GLOBALS['ldap_bindpw'],
                           $GLOBALS['ldap_network_timeout'])
                    ->andReturn(["ldap_connection", false]);

        $ltbLDAPMock->shouldreceive('ldapSort')
                    ->with(
                           \Mockery::on(function(array &$ent) {
                               if (!is_array($ent))
                               {
                                   return false;
                               }
                               else
                               {
                                   // modify the array to sort it
                                   $ent = [
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
                                              0 => 'aaaaaa'
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
                                              0 => 'zzzzzz'
                                          ]
                                      ]
                                   ];
                                   return true;
                               }
                           }),
                           "sn")
                    ->andReturn(true);

        $phpLDAPMock = Mockery::mock('overload:\Ltb\PhpLDAP');

        $phpLDAPMock->shouldreceive('ldap_search')
                    ->with("ldap_connection",
                           $GLOBALS['ldap_user_base'],
                           "(objectClass=inetOrgPerson)",
                           [0 => 'cn', 1 => 'sn', 2 => 'uid', 3 => 'mail', 4 => 'mobile', 5 => 'cn', 6 => 'sn'],
                           0,
                           $GLOBALS['ldap_size_limit']
                          )
                    ->andReturn("ldap_search_result");

        $phpLDAPMock->shouldreceive('ldap_errno')
                    ->with("ldap_connection")
                    ->andReturn(0);

        $phpLDAPMock->shouldreceive('ldap_count_entries')
                    ->with("ldap_connection", "ldap_search_result")
                    ->andReturn(2);

        $phpLDAPMock->shouldreceive('ldap_get_entries')
                    ->with("ldap_connection","ldap_search_result")
                    ->andReturn($entries);

        list($ldap,$result,$nb_entries,$res_entries,$size_limit_reached) = 
                  Ltb\LtbUtil::search( "(objectClass=inetOrgPerson)",
                                       array("cn", "sn")
                                     );

        $this->assertEquals($ldap, "ldap_connection", "Error while getting ldap_connection in search function");
        $this->assertFalse($result, "Error message returned while connecting to LDAP server in search function");
        $this->assertEquals(2, $nb_entries, "Wrong number of entries returned by search function");
        $this->assertEquals("testcn2", $res_entries[0]["cn"][0], "Wrong cn received in first entry");
        $this->assertEquals("testcn1", $res_entries[1]["cn"][0], "Wrong cn received in second entry");
        $this->assertFalse($size_limit_reached, "Unexpected size limit reached in search function");
    }

}
