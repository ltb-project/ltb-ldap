<?php namespace Ltb;

/**
 * 
 */
final class Password {
    # Create SSHA password
    static function make_ssha_password($password): string {
        $salt = random_bytes(4);
        return "{SSHA}" . base64_encode(pack("H*", sha1($password . $salt)) . $salt);
    }
    
    static function check_ssha_password($password, $hash): bool {
        $salt = substr(base64_decode(substr($hash, 6)), 20);
        $hash2 = "{SSHA}" . base64_encode(pack("H*", sha1($password . $salt)) . $salt);
        return ($hash === $hash2);
    }

    # Create SSHA256 password
    static function make_ssha256_password($password): string {
        $salt = random_bytes(4);
        return "{SSHA256}" . base64_encode(pack("H*", hash('sha256', $password . $salt)) . $salt);
    }
    
    static function check_ssha256_password($password, $hash): bool {
        $salt = substr(base64_decode(substr($hash, 9)), 32);
        $hash2 = "{SSHA256}".base64_encode(hash('sha256', $password.$salt, true).$salt);
        return ($hash === $hash2);
    }

    # Create SSHA384 password
    static function make_ssha384_password($password): string {
        $salt = random_bytes(4);
        return "{SSHA384}" . base64_encode(pack("H*", hash('sha384', $password . $salt)) . $salt);
    }
    
    static function check_ssha384_password($password, $hash): bool {
        $salt = substr(base64_decode(substr($hash, 9)), 48);
        $hash2 = "{SSHA384}".base64_encode(hash('sha384', $password.$salt, true).$salt);
        return ($hash === $hash2);
    }

    # Create SSHA512 password
    static function make_ssha512_password($password): string {
        $salt = random_bytes(4);
        return "{SSHA512}" . base64_encode(pack("H*", hash('sha512', $password . $salt)) . $salt);
    }
    
    static function check_ssha512_password($password, $hash): bool {
        $salt = substr(base64_decode(substr($hash, 9)), 64);   //salt of given hash (remove {SSHA512}, decode it, and only the bits after 512/8=64 bits)
        $hash2 = "{SSHA512}".base64_encode(hash('sha512', $password.$salt, true).$salt);
        return ($hash === $hash2);
    }

    # Create SHA password
    static function make_sha_password($password): string {
        return "{SHA}" . base64_encode(pack("H*", sha1($password)));
    }
    
    static function check_sha_password($password, $hash): bool {
        return ($hash === make_sha_password($password));
    }

    # Create SHA256 password
    static function make_sha256_password($password): string {
        return "{SHA256}" . base64_encode(pack("H*", hash('sha256', $password)));
    }
    
    static function check_sha256_password($password, $hash): bool {
        return ($hash === make_sha256_password($password));
    }

    # Create SHA384 password
    static function make_sha384_password($password): string {
        return "{SHA384}" . base64_encode(pack("H*", hash('sha384', $password)));
    }
    
    static function check_sha384_password($password, $hash): bool {
        return ($hash === make_sha384_password($password));
    }

    # Create SHA512 password
    static function make_sha512_password($password): string {
        return "{SHA512}" . base64_encode(pack("H*", hash('sha512', $password)));
    }
    
    static function check_sha512_password($password, $hash): bool {
        return ($hash === make_sha512_password($password));
    }

    # Create SMD5 password
    static function make_smd5_password($password): string {
        $salt = random_bytes(4);
        return "{SMD5}" . base64_encode(pack("H*", md5($password . $salt)) . $salt);
    }
    
    static function check_smd5_password($password, $hash): bool {
        $salt = substr(base64_decode(substr($hash, 6)), 16);
        $hash2 = "{SMD5}" . base64_encode(pack("H*", md5($password . $salt)) . $salt);
        return ($hash === $hash2);
    }

    # Create MD5 password
    static function make_md5_password($password): string {
        return "{MD5}" . base64_encode(pack("H*", md5($password)));
    }
    
    static function check_md5_password($password, $hash): bool {
        return ($hash === make_md5_password($password));
    }

    # Create CRYPT password
    static function make_crypt_password($password, $hash_options): string {

        $salt_length = 2;
        if ( isset($hash_options['crypt_salt_length']) ) {
            $salt_length = $hash_options['crypt_salt_length'];
        }

        // Generate salt
        $possible = '0123456789'.
                    'abcdefghijklmnopqrstuvwxyz'.
                    'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
                    './';
        $salt = "";

        while( strlen( $salt ) < $salt_length ) {
            $salt .= substr( $possible, random_int( 0, strlen( $possible ) - 1 ), 1 );
        }

        if ( isset($hash_options['crypt_salt_prefix']) ) {
            $salt = $hash_options['crypt_salt_prefix'] . $salt;
        }

        return '{CRYPT}' . crypt( $password,  $salt);
    }
    
    static function check_crypt_password($password, $hash): bool {
        return password_verify($password, substr($hash, 7));
    }

    # Create ARGON2 password
    static function make_argon2_password($password, $hash_options): string {
        if (!isset($hash_options['memory_cost'])) { $hash_options['memory_cost'] = 4096; } 
        if (!isset($hash_options['time_cost'])) { $hash_options['time_cost'] = 3; } 
        if (!isset($hash_options['threads'])) { $hash_options['threads'] = 1; }

        return '{ARGON2}' . password_hash($password,PASSWORD_ARGON2I,$hash_options);
    }
    
    static function check_argon2_password($password, $hash): bool {
        return password_verify($password, substr($hash, 8));
    }

    # Create MD4 password (Microsoft NT password format)
    static function make_md4_password($password): string {
        if (function_exists('hash')) {
            return strtoupper( hash( "md4", iconv( "UTF-8", "UTF-16LE", $password ) ) );
        } else {
            return strtoupper( bin2hex( mhash( MHASH_MD4, iconv( "UTF-8", "UTF-16LE", $password ) ) ) );
        }
    }
    
    static function check_md4_password($password, $hash): bool {
        return ($hash === make_md4_password($password));
    }

    /**
     * Generate a hash with the given algorithm and options.
     * @param string $password the password to hash
     * @param string $hash the algorithm to be used
     * @param array $hash_options additional options to be used by the hashing algorithm
     * @return string
     */
    static function make_password($password, $hash, $hash_options): string {
        switch ($hash) {
            case "clear":
                return $password;
            case "SSHA":
                return make_ssha_password($password);
            case "SSHA256":
                return make_ssha256_password($password);
            case "SSHA384":
                return make_ssha384_password($password);
            case "SSHA512":
                return make_ssha512_password($password);
            case "SHA":
                return make_sha_password($password);
            case "SHA256":
                return make_sha256_password($password);
            case "SHA384":
                return make_sha384_password($password);
            case "SHA512":
                return make_sha512_password($password);
            case "SMD5":
                return make_smd5_password($password);
            case "MD5":
                return make_md5_password($password);
            case "CRYPT":
                return make_crypt_password($password, $hash_options);
            case "ARGON2":
                return make_argon2_password($password, $hash_options);
            case "NTLM":
                return make_md4_password($password);
            default:
                return $password;
        }
    }
    /**
     * @function check_password(string $password, string $hash, string $algo)
     * Check if a password matches to a given hash.
     * @param string $password (new) Password to match against the hash
     * @param string $hash the stored hash the password has to match
     * @param string $algo the hashing algorithm
     * @return bool true: password and hash do match
     */
    static function check_password($password, $hash, $algo): bool {
        switch ($algo) {
            case "clear":
                return $password == $hash;
            case "SSHA":
                return check_ssha_password($password, $hash);
            case "SSHA256":
                return check_ssha256_password($password, $hash);
            case "SSHA384":
                return check_ssha384_password($password, $hash);
            case "SSHA512":
                return check_ssha512_password($password, $hash);
            case "SHA":
                return check_sha_password($password, $hash);
            case "SHA256":
                return check_sha256_password($password, $hash);
            case "SHA384":
                return check_sha384_password($password, $hash);
            case "SHA512":
                return check_sha512_password($password, $hash);
            case "SMD5":
                return check_smd5_password($password, $hash);
            case "MD5":
                return check_md5_password($password, $hash);
            case "CRYPT":
                return check_crypt_password($password, $hash);
            case "ARGON2":
                return check_argon2_password($password, $hash);
            case "NTLM":
                return check_md4_password($password, $hash);
            default:
                return $password == $hash;
        }
    }

    # Create AD password (Microsoft Active Directory password format)
    static function make_ad_password($password): string {
        $password = "\"" . $password . "\"";
        $adpassword = mb_convert_encoding($password, "UTF-16LE", "UTF-8");
        return $adpassword;
    }

    /**
     * @function check_hash_type(\LDAP\Connection|array $ldap, array|string $dn, string $pwdattribute)
     * Read the password attribute and return the algorithm used to hash the password.
     * @param string $pwdattribute the attribute where the hash is stored
     * @return string algorithm used with the hash
     */
    static function get_hash_type($pwdattribute): string {
        $matches = array();
        if (isset($userpassword) && preg_match('/^\{(\w+)\}/', $userpassword[0], $matches)) {
            return strtoupper($matches[1]);
        }
    }
    
    static function set_samba_data($userdata, $samba_options, $password): void {
        $time = time();
        $userdata["sambaNTPassword"] = make_md4_password($password);
        $userdata["sambaPwdLastSet"] = $time;
        if ( isset($samba_options['min_age']) && $samba_options['min_age'] > 0 ) {
             $userdata["sambaPwdCanChange"] = $time + ( $samba_options['min_age'] * 86400 );
        }
        if ( isset($samba_options['max_age']) && $samba_options['max_age'] > 0 ) {
             $userdata["sambaPwdMustChange"] = $time + ( $samba_options['max_age'] * 86400 );
        }
        if ( isset($samba_options['expire_days']) && $samba_options['expire_days'] > 0 ) {
             $userdata["sambaKickoffTime"] = $time + ( $samba_options['expire_days'] * 86400 );
        }
    }
    
    static function set_ad_data($userdata, $ad_options, $password): void {
        $userdata["unicodePwd"] = $password;
        if ( $ad_options['force_unlock'] ) {
            $userdata["lockoutTime"] = 0;
        }
        if ( $ad_options['force_pwd_change'] ) {
            $userdata["pwdLastSet"] = 0;
        }
    }
    
    static function set_shadow_data($userdata, $shadow_options): void {
        $time = time();
        if ( $shadow_options['update_shadowLastChange'] ) {
            $userdata["shadowLastChange"] = floor($time / 86400);
        }

        if ( $shadow_options['update_shadowExpire'] ) {
            if ( $shadow_options['shadow_expire_days'] > 0) {
              $userdata["shadowExpire"] = floor(($time / 86400) + $shadow_options['shadow_expire_days']);
            } else {
              $userdata["shadowExpire"] = $shadow_options['shadow_expire_days'];
            }
        }
    }

}
