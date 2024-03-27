<?php namespace Ltb;


final class Ldap {

    # LDAP Functions 

    static function connect($ldap_url, $ldap_starttls, $ldap_binddn, $ldap_bindpw, $ldap_network_timeout, $ldap_krb5ccname) {

        # Connect to LDAP
        $ldap = \Ltb\PhpLDAP::ldap_connect($ldap_url);
        \Ltb\PhpLDAP::ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        \Ltb\PhpLDAP::ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        if ( isset($ldap_network_timeout) ) {
            \Ltb\PhpLDAP::ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, $ldap_network_timeout);
        }

        if ( $ldap_starttls && !\Ltb\PhpLDAP::ldap_start_tls($ldap) ) {
            error_log("LDAP - Unable to use StartTLS");
            return array(false, "ldaperror");
        }

        # Bind
        if ( isset($ldap_binddn) && isset($ldap_bindpw) ) {
            $bind = \Ltb\PhpLDAP::ldap_bind($ldap, $ldap_binddn, $ldap_bindpw);
        } elseif ( isset($ldap_krb5ccname) ) {
            putenv("KRB5CCNAME=".$ldap_krb5ccname);
            $bind = \Ltb\PhpLDAP::ldap_sasl_bind($ldap, NULL, NULL, 'GSSAPI') or error_log('LDAP - GSSAPI Bind failed');
        } else {
            $bind = \Ltb\PhpLDAP::ldap_bind($ldap);
        }

        if ( !$bind ) {
            $errno = \Ltb\PhpLDAP::ldap_errno($ldap);
            if ( $errno ) {
                error_log("LDAP - Bind error $errno  (".\Ltb\PhpLDAP::ldap_error($ldap).")");
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
            $search = \Ltb\PhpLDAP::ldap_search($ldap, $ldap_base, $ldap_filter, array($key, $value) );

            $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

            if ( $errno ) {
                error_log("LDAP - Search error $errno  (".\Ltb\PhpLDAP::ldap_error($ldap).")");
            } else {
                $entries = \Ltb\PhpLDAP::ldap_get_entries($ldap, $search);
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
            $check = \Ltb\PhpLDAP::ldap_read($ldap, '', '(objectClass=*)', [$check_attribute]);
            $entries = \Ltb\PhpLDAP::ldap_get_entries($ldap, $check);
            if (in_array(LDAP_CONTROL_SORTREQUEST, $entries[0]['supportedcontrol'],true)) {
                # server side sort
                $controls=[['oid' => LDAP_CONTROL_SORTREQUEST, 'value' => [['attr'=>$sortby]]]];
                # if $sortby is not in $attributes ? what to do ?
                $ldap_result = \Ltb\PhpLDAP::ldap_search($ldap, $ldap_base, $ldap_filter, $attributes, 0, $ldap_size_limit, -1, LDAP_DEREF_NEVER, $controls );
                $errno = \Ltb\PhpLDAP::ldap_errno($ldap);
                if ( $errno === 0 )
                {
                    $entries=\Ltb\PhpLDAP::ldap_get_entries($ldap, $ldap_result);
                }
            }
        }

        if (!isset($errno))
        {
            $ldap_result = \Ltb\PhpLDAP::ldap_search($ldap, $ldap_base, $ldap_filter, $attributes, 0, $ldap_size_limit);
            $errno = \Ltb\PhpLDAP::ldap_errno($ldap);
            if ( $errno === 0 )
            {
                $entries=\Ltb\PhpLDAP::ldap_get_entries($ldap, $ldap_result);
                Ldap::ldapSort($entries,$sortby);
            }
            else {
                var_dump($errno);
            }
        }

        return array($ldap_result,$errno,$entries);
    }


}
?>
