<?php

require __DIR__ . '/../../vendor/autoload.php';
use PHPUnit\Framework\TestCase;

// global variable for ldap_get_mail_for_notification function
$GLOBALS['mail_attributes'] = array("mail");

final class LdapTest extends TestCase
{

    public $host = "ldap://127.0.0.1:33389/";
    public $managerDN = "cn=admin,dc=fusioniam,dc=org";
    public $managerPW = "secret";
    public $attributes = array("cn");
    public $context = "dc=fusioniam,dc=org";

    public $user_branch = "ou=users,o=acme,dc=fusioniam,dc=org";
    public $ldap_entry_dn1 = "uid=test,ou=users,o=acme,dc=fusioniam,dc=org";
    public $ldap_entry1 = [
        "objectclass" => array("inetOrgPerson", "organizationalPerson", "person"),
        "cn" => array("test1", "test2", "test3"),
        "sn" => "test",
        "uid" => "test",
        "userPassword" => "secret",
        "mail" => array("test1@domain.com", "test2@domain.com")
    ];

    /*
       Function setting up the environement, executed before each test
       add a test entry
    */
    protected function setUp(): void
    {

        error_reporting(E_ALL);

        $ldap = ldap_connect($this->host);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);

        // binding to ldap server
        $ldapbind = ldap_bind($ldap, $this->managerDN, $this->managerPW);

        // search for ldap entry
        $sr = ldap_search($ldap, $this->user_branch, "(uid=test)", $this->attributes);
        if( $sr )
        {
            $info = ldap_get_entries($ldap, $sr);
            if( $info["count"] == 0)
            {
                // if it does not exist, add the entry
                $r = ldap_add($ldap, $this->ldap_entry_dn1, $this->ldap_entry1);
            }
        }

        ldap_unbind($ldap);
    }

    /*
       Function cleaning up the environement, executed after each test
       remove the test entry created during setup
    */
    protected function tearDown(): void
    {

        $ldap = ldap_connect($this->host);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);

        // binding to ldap server
        $ldapbind = ldap_bind($ldap, $this->managerDN, $this->managerPW);

        // search for ldap entry
        $sr = ldap_search($ldap, $this->user_branch, "(uid=test)", $this->attributes);
        if( $sr )
        {
            $info = ldap_get_entries($ldap, $sr);
            if( $info["count"] == 1)
            {
                // if it exists, delete the entry
                $r = ldap_delete($ldap, $this->ldap_entry_dn1);
            }
        }

        ldap_unbind($ldap);
    }


    public function test_connect(): void
    {

        list($ldap, $msg) = Ltb\Ldap::connect($this->host, false, $this->managerDN, $this->managerPW, 10, null);

        $this->assertNotFalse($ldap, "Error while connecting to LDAP server");
        $this->assertFalse($msg, "Error message returned while connecting to LDAP server");
    }

    public function test_get_list(): void
    {

        $ldap = ldap_connect($this->host);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);

        // binding to ldap server
        $ldapbind = ldap_bind($ldap, $this->managerDN, $this->managerPW);

        // return hashmap: [ cn_value => sn_value ]
        $result = Ltb\Ldap::get_list($ldap, $this->user_branch, "(uid=test)", "cn","sn");

        $this->assertEquals(array_keys($result)[0], 'test1', "not getting test1 as key in get_list function");
        $this->assertEquals($result["test1"], 'test', "not getting test as value in get_list function");

    }

}
