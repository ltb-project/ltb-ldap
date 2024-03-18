<?php namespace Ltb;


final class Ldap {

    # LDAP Functions 

    static function connect($ldap_url, $ldap_starttls, $ldap_binddn, $ldap_bindpw, $ldap_network_timeout, $ldap_krb5ccname) {

        # Connect to LDAP
        $ldap = \ldap_connect($ldap_url);
        \ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        \ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        if ( isset($ldap_network_timeout) ) {
            ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, $ldap_network_timeout);
        }

        if ( $ldap_starttls && !ldap_start_tls($ldap) ) {
            error_log("LDAP - Unable to use StartTLS");
            return array(false, "ldaperror");
        }

        # Bind
        if ( isset($ldap_binddn) && isset($ldap_bindpw) ) {
            $bind = ldap_bind($ldap, $ldap_binddn, $ldap_bindpw);
        } elseif ( isset($ldap_krb5ccname) ) {
            putenv("KRB5CCNAME=".$ldap_krb5ccname);
            $bind = ldap_sasl_bind($ldap, NULL, NULL, 'GSSAPI') or error_log('LDAP - GSSAPI Bind failed');
        } else {
            $bind = ldap_bind($ldap);
        }

        if ( !$bind ) {
            $errno = ldap_errno($ldap);
            if ( $errno ) {
                error_log("LDAP - Bind error $errno  (".ldap_error($ldap).")");
            } else {
                error_log("LDAP - Bind error");
            }
            return array(false, "ldaperror");
        }

        return array($ldap, false);
    }

    static function get_list($ldap, $ldap_base, $ldap_filter, $key, $value) {

        $return = array();

        if ($ldap) {

            # Search entry
            $search = ldap_search($ldap, $ldap_base, $ldap_filter, array($key, $value) );

            $errno = ldap_errno($ldap);

            if ( $errno ) {
                error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            } else {
                $entries = ldap_get_entries($ldap, $search);
                for ($i=0; $i<$entries["count"]; $i++) {
                    if(isset($entries[$i][$key][0])) {
                        $return[$entries[$i][$key][0]] = isset($entries[$i][$value][0]) ? $entries[$i][$value][0] : $entries[$i][$key][0];
                    }
                }
            }
        }

        return $return;
    }

    # if key is not found in attributes, order of entries is preserved
    static function ldapSort(array &$entries, $key)
    {
        # 'count' is an additionnal attribute of ldap entries that will be preserved
        # remove it since lost by usort ( changed to integer index )
        $count=$entries['count'];
        unset($entries['count']);

        $sort_key=$key;

        usort($entries,
              fn($a, $b) =>
              ( is_array($a) and is_array($b) ) ?
                ( array_key_exists($sort_key,$a) ?
                    ( array_key_exists($sort_key,$b) ? $a[$sort_key][0] <=> $b[$sort_key][0] : 1 )
                  : ( array_key_exists($sort_key,$b) ? -1 : 0 ))
              : 0
        );


        # preserve count since sorting should not change number of elements.
        $entries['count']=$count;

        return true;

    }

    # not yet fully tested, please use ldapSort directly
    #
    # ldap_search + ldap_sort combined done at server side if possible
    # if not supported fallback on client sorting.
    static function sorted_search($ldap, $ldap_base, $ldap_filter, $attributes, $sortby, $ldap_size_limit) {

        if (isset($sortby) and $sortby)
        {
            $check_attribute='supportedControl';
            $check = ldap_read($ldap, '', '(objectClass=*)', [$check_attribute]);
            $entries=ldap_get_entries($ldap, $check);
            if (in_array(LDAP_CONTROL_SORTREQUEST, $entries[0]['supportedcontrol'],true)) {
                # server side sort
                $controls=[['oid' => LDAP_CONTROL_SORTREQUEST, 'value' => [['attr'=>$sortby]]]];
                # if $sortby is not in $attributes ? what to do ?
                $ldap_result = ldap_search($ldap, $ldap_base, $ldap_filter, $attributes, 0, $ldap_size_limit, -1, LDAP_DEREF_NEVER, $controls );
                $errno = ldap_errno($ldap);
                if ( $errno === 0 )
                {
                    $entries=ldap_get_entries($ldap, $ldap_result);
                }
            }
        }

        if (!isset($errno))
        {
            $ldap_result = ldap_search($ldap, $ldap_base, $ldap_filter, $attributes, 0, $ldap_size_limit);
            $errno = ldap_errno($ldap);
            if ( $errno === 0 )
            {
                $entries=ldap_get_entries($ldap, $ldap_result);
                Ldap::ldapSort($entries,$sortby);
            }
            else {
                var_dump($errno);
            }
        }

        return array($ldap_result,$errno,$entries);
    }
    
    /**
     * Gets the value of the password attribute
     * @param \LDAP\Connection|array $ldap An LDAP\Connection instance, returned by ldap_connect()
     * @param string $dn the dn of the user
     * @param type $pwdattribute the Attribute that contains the password
     * @return string the value of $pwdattribute
     */
    static function get_password_value($ldap, $dn, $pwdattribute): string {
        $search_userpassword = ldap_read($ldap, $dn, "(objectClass=*)", array($pwdattribute));
        if ($search_userpassword) {
            return ldap_get_values($ldap, ldap_first_entry($ldap, $search_userpassword), $pwdattribute);
        }
    }
    
    /**
     * Changes the password of an user while binded as the user in an Active Directory
     * @param \LDAP\Connection|array $ldap An LDAP\Connection instance, returned by ldap_connect()
     * @param string $dn the dn of the user
     * @param string $oldpassword the old password
     * @param string $password the new password
     * @return array [$error_code, $error_msg]
     */
    static function change_ad_password_as_user($ldap, $dn, $oldpassword, $password): array {
        # The AD password change procedure is modifying the attribute unicodePwd by
        # first deleting unicodePwd with the old password and them adding it with the
        # the new password
        $oldpassword_hashed = make_ad_password($oldpassword);

        $modifications = array(
            array(
                "attrib" => "unicodePwd",
                "modtype" => LDAP_MODIFY_BATCH_REMOVE,
                "values" => array($oldpassword_hashed),
            ),
            array(
                "attrib" => "unicodePwd",
                "modtype" => LDAP_MODIFY_BATCH_ADD,
                "values" => array($password),
            ),
        );

        ldap_modify_batch($ldap, $dn, $modifications);
        $error_code = ldap_errno($ldap);
        $error_msg = ldap_error($ldap);
        return array($error_code, $error_msg);
    }
    
    static protected function get_ppolicy_error_code($ctrls) {
        if (isset($ctrls[LDAP_CONTROL_PASSWORDPOLICYRESPONSE])) {
            $value = $ctrls[LDAP_CONTROL_PASSWORDPOLICYRESPONSE]['value'];
            if (isset($value['error'])) {
                $ppolicy_error_code = $value['error'];
                error_log("LDAP - Ppolicy error code: $ppolicy_error_code");
                return $ppolicy_error_code;
            }
        }
        return false;
    }

    /**
     * Changes the Password using extended password modification
     * @param \LDAP\Connection|array $ldap An LDAP\Connection instance, returned by ldap_connect()
     * @param string $dn the dn of the user
     * @param string $oldpassword the old password
     * @param string $password the new password
     * @param array $userdata
     * @param bool $use_ppolicy_control
     * @return array 0: error_code, 1: error_msg, 2: ppolicy_error_code
     */
    static function change_password_with_exop($ldap, $dn, $oldpassword, $password, $use_ppolicy_control): array {
        $ppolicy_error_code = false;
        $exop_passwd = FALSE;
        if ( $use_ppolicy_control ) {
            $ctrls = array();
            $exop_passwd = ldap_exop_passwd($ldap, $dn, $oldpassword, $password, $ctrls);
            $error_code = ldap_errno($ldap);
            $error_msg = ldap_error($ldap);
            if (!$exop_passwd) {
                $ppolicy_error_code = self::get_ppolicy_error_code($ctrls);
            }
        } else {
            $exop_passwd = ldap_exop_passwd($ldap, $dn, $oldpassword, $password);
            $error_code = ldap_errno($ldap);
            $error_msg = ldap_error($ldap);
        }
        return array($error_code, $error_msg, $ppolicy_error_code);
    }
    
    /**
     * Changes attributes (and password) using Password Policy Control
     * @param \LDAP\Connection|array $ldap An LDAP\Connection instance, returned by ldap_connect()
     * @param string $dn the dn of the user
     * @param array $userdata the array, containing the new (hashed) password
     * @return array 0: error_code, 1: error_msg, 2: ppolicy_error_code
     */
    static function modify_attributes_using_ppolicy($ldap, $dn, $userdata): array {
        $error_code = "";
        $error_msg = "";
        $ctrls = array();
        $ppolicy_error_code = false;
        $ppolicy_replace = ldap_mod_replace_ext($ldap, $dn, $userdata, [['oid' => LDAP_CONTROL_PASSWORDPOLICYREQUEST]]);
        if (ldap_parse_result($ldap, $ppolicy_replace, $error_code, $matcheddn, $error_msg, $referrals, $ctrls)) {
            $ppolicy_error_code = self::get_ppolicy_error_code($ctrls);
        }
        return array($error_code, $error_msg, $ppolicy_error_code);
    }
    
    /**
     * Changes attributes (and password)
     * @param \LDAP\Connection|array $ldap An LDAP\Connection instance, returned by ldap_connect()
     * @param string $dn the dn of the user
     * @param array $userdata the array, containing the new (hashed) password
     * @return array 0: error_code, 1: error_msg
     */
    static function modify_attributes($ldap, $dn, $userdata): array {
        ldap_mod_replace($ldap, $dn, $userdata);
        $error_code = ldap_errno($ldap);
        $error_msg = ldap_error($ldap);
        return array($error_code, $error_msg);
    }

    const PPOLICY_ERROR_CODE_TO_RESULT_MAPPER = [
            0 => "passwordExpired",
            1 => "accountLocked",
            2 => "changeAfterReset",
            3 => "passwordModNotAllowed",
            4 => "mustSupplyOldPassword",
            5 => "badquality",
            6 => "tooshort",
            7 => "tooyoung",
            8 => "inhistory"
        ];

}
?>
