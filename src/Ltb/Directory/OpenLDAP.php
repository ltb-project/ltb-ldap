<?php

namespace Ltb\Directory;
use \DateTime;
use \DateInterval;

class OpenLDAP implements \Ltb\Directory
{
    public function isLocked($ldap, $dn, $config) : bool {

        # Get entry
        $search = \Ltb\PhpLDAP::ldap_read($ldap, $dn, "(objectClass=*)", array('pwdaccountlockedtime'));
        $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            return false;
        } else {
            $entry = \Ltb\PhpLDAP::ldap_get_entries($ldap, $search);
        }

        # Get pwdAccountLockedTime
        $pwdAccountLockedTime = $entry[0]['pwdaccountlockedtime'][0];

        if (!$pwdAccountLockedTime) {
            return false;
        }

        if ( $pwdAccountLockedTime === "000001010000Z" ) {
            return true;
        }

        $unlockDate = $this->getUnlockDate($ldap, $dn, $config);

        if ( $unlockDate and time() <= $unlockDate->getTimestamp() ) {
            return true;
        }

        return false;
    }

    public function getUnlockDate($ldap, $dn, $config) : ?DateTime {

        $unlockDate = NULL;

        # Get entry
        $search = \Ltb\PhpLDAP::ldap_read($ldap, $dn, "(objectClass=*)", array('pwdaccountlockedtime'));
        $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            return $unlockDate;
        } else {
            $entry = \Ltb\PhpLDAP::ldap_get_entries($ldap, $search);
        }

        # Get pwdAccountLockedTime
        $pwdAccountLockedTime = $entry[0]['pwdaccountlockedtime'][0];

        if (!$pwdAccountLockedTime) {
            return $unlockDate;
        }

        # Get lockoutDuration
        $lockoutDuration = $config["lockoutDuration"];

        if ( $pwdAccountLockedTime === "000001010000Z" ) {
            return $unlockDate;
        } else if (isset($lockoutDuration) and ($lockoutDuration > 0)) {
            $lockDate = \Ltb\Date::ldapDate2phpDate($pwdAccountLockedTime);
            $unlockDate = date_add( $lockDate, new DateInterval('PT'.$lockoutDuration.'S'));
        }

        return $unlockDate;
    }

    public function getLockoutDuration($ldap, $dn, $config) : ?int {

        $lockoutDuration = 0;

        # If lockoutDuration is forced in config
        if (isset($config['lockoutDuration'])) {
            return $config['lockoutDuration'];
        }

        # Else get password policy configuration
        $ppolicy_search = \Ltb\PhpLDAP::ldap_read($ldap, $config['pwdPolicy'], "(objectClass=*)", array('pwdlockout', 'pwdlockoutduration'));
        $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            return $lockoutDuration;
        } else {
            $ppolicy_entry = \Ltb\PhpLDAP::ldap_get_entries($ldap, $ppolicy_search);
        }

        $pwdLockout = strtolower($ppolicy_entry[0]['pwdlockout'][0]) == "true" ? true : false;
        $pwdLockoutDuration = $ppolicy_entry[0]['pwdlockoutduration'][0];

        if ($pwdLockout) {
            $lockoutDuration = $pwdLockoutDuration;
        }

        return $lockoutDuration;
    }

    public function canLockAccount($ldap, $dn, $config) : bool {

        # Search password policy
        $ppolicy_search = \Ltb\PhpLDAP::ldap_read($ldap, $config['pwdPolicy'], "(objectClass=*)", array('pwdlockout'));
        $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            return false;
        } else {
            $ppolicy_entry = \Ltb\PhpLDAP::ldap_get_entries($ldap, $ppolicy_search);
        }

        $pwdLockout = strtolower($ppolicy_entry[0]['pwdlockout'][0]) == "true" ? true : false;

        return $pwdLockout;
    }

    public function lockAccount($ldap, $dn) : bool {

        $modification = \Ltb\PhpLdap::ldap_mod_replace($ldap, $dn, array("pwdAccountLockedTime" => array("000001010000Z")));
        $errno = ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Lock account error $errno  (".ldap_error($ldap).")");
            return false;
        } else {
            return true;
        }
    }

    public function unlockAccount($ldap, $dn) : bool {

        $modification = \Ltb\PhpLdap::ldap_mod_replace($ldap, $dn, array("pwdAccountLockedTime" => array()));
        $errno = ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Unlock account error $errno  (".ldap_error($ldap).")");
            return false;
        } else {
            return true;
        }
    }

    public function isPasswordExpired($ldap, $dn, $config) : bool {

        # Get entry
        $search = \Ltb\PhpLDAP::ldap_read($ldap, $dn, "(objectClass=*)", array('pwdchangedtime'));
        $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            return false;
        } else {
            $entry = \Ltb\PhpLDAP::ldap_get_entries($ldap, $search);

        }

        # Get pwdChangedTime
        $pwdChangedTime = $entry[0]['pwdchangedtime'][0];

        if (!$pwdChangedTime) {
            return false;
        }

        # Get password expiration date
        $expirationDate = $this->getPasswordExpirationDate($ldap, $dn, $config);

        if (!$expirationDate) {
            return false;
        }

        if ($expirationDate and time() >= $expirationDate->getTimestamp()) {
            return true;
        }

        return false;
    }

    public function getPasswordExpirationDate($ldap, $dn, $config) : ?DateTime {

        $expirationDate = NULL;

        # Get entry
        $search = \Ltb\PhpLDAP::ldap_read($ldap, $dn, "(objectClass=*)", array('pwdchangedtime'));
        $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            return $expirationDate;
        } else {
            $entry = \Ltb\PhpLDAP::ldap_get_entries($ldap, $search);
        }

        # Get pwdChangedTime
        $pwdChangedTime = $entry[0]['pwdchangedtime'][0];

        if (!$pwdChangedTime) {
            return $expirationDate;
        }

        # Get pwdMaxAge
        $pwdMaxAge = $config["pwdMaxAge"];

        # Compute expiration date
        if ($pwdMaxAge) {
            $changedDate = \Ltb\Date::ldapDate2phpDate($pwdChangedTime);
            $expirationDate = date_add( $changedDate, new DateInterval('PT'.$pwdMaxAge.'S'));
        }

        return $expirationDate;
    }

    public function getPasswordMaxAge($ldap, $dn, $config) : ?int {

        $pwdMaxAge = 0;

        # If pwdMaxAge is forced in config
        if (isset($config['pwdMaxAge'])) {
            return $config['pwdMaxAge'];
        }

        # Else get password policy configuration
        $ppolicy_search = \Ltb\PhpLDAP::ldap_read($ldap, $config['pwdPolicy'], "(objectClass=*)", array('pwdmaxage'));
        $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            return $pwdMaxAge;
        } else {
            $ppolicy_entry = \Ltb\PhpLDAP::ldap_get_entries($ldap, $ppolicy_search);
        }

        if ( $ppolicy_entry[0]['pwdmaxage'] ) {
            $pwdMaxAge =  $ppolicy_entry[0]['pwdmaxage'][0];
        }

        return $pwdMaxAge;
    }

    public function modifyPassword($ldap, $dn, $password, $forceReset) : bool {

        $changes = array('userPassword' => $password);

        if ($forceReset) {
            $changes['pwdReset'] = 'TRUE';
        }

        $update = \Ltb\PhpLDAP::ldap_mod_replace($ldap, $dn, $changes);
        $errno = ldap_errno($ldap);

        if ($errno) {
            error_log("LDAP - Modify password error $errno  (".ldap_error($ldap).")");
            return false;
        } else {
            return true;
        }
    }

    public function resetAtNextConnection($ldap, $dn) : bool {

        # Get entry
        $search = \Ltb\PhpLDAP::ldap_read($ldap, $dn, "(objectClass=*)", array('pwdreset'));
        $errno = \Ltb\PhpLDAP::ldap_errno($ldap);

        if ( $errno ) {
            error_log("LDAP - Search error $errno  (".ldap_error($ldap).")");
            return $expirationDate;
        } else {
            $entry = \Ltb\PhpLDAP::ldap_get_entries($ldap, $search);
        }

        if ($entry[0]['pwdreset'] and $entry[0]['pwdreset'][0] === "TRUE") {
            return true;
        } else {
            return false;
        }
    }

    public function enableAccount($ldap, $dn) : bool {
        // Not implemented
        return false;
    }

    public function disableAccount($ldap, $dn) : bool {
        // Not implemented
        return false;
    }

    public function isAccountEnabled($ldap, $dn) : bool {
        // Not implemented
        return true;
    }
}
