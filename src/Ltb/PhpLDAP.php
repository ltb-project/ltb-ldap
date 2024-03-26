<?php

namespace Ltb;

class PhpLDAP {

  // Simple class for calling php-ldap functions

  public static function ldap_get_attributes($ldap, $entry)
  {
    return ldap_get_attributes($ldap, $entry);
  }

  public static function ldap_get_values($ldap, $entry, $attribute)
  {
    return ldap_get_values($ldap, $entry, $attribute);
  }

  public static function ldap_connect($ldap_url)
  {
    return ldap_connect($ldap_url);
  }

  public static function ldap_set_option($ldap, $option, $value)
  {
    return ldap_set_option($ldap, $option, $value);
  }

  public static function ldap_start_tls($ldap)
  {
    return ldap_start_tls($ldap);
  }

  public static function ldap_bind($ldap, $dn = null, $password = null)
  {
    return ldap_bind($ldap, $dn, $password);
  }

  public static function ldap_sasl_bind(...$args)
  {
    return ldap_sasl_bind(...$args);
  }

  public static function ldap_errno($ldap)
  {
    return ldap_errno($ldap);
  }

  public static function ldap_error($ldap)
  {
    return ldap_error($ldap);
  }

  public static function ldap_search(...$args)
  {
    return ldap_search(...$args);
  }

  public static function ldap_get_entries($ldap, $search)
  {
    return ldap_get_entries($ldap, $search);
  }

  public static function ldap_read(...$args)
  {
    return ldap_read(...$args);
  }

  public static function ldap_count_entries($ldap, $result)
  {
    return ldap_count_entries($ldap, $result);
  }

}
?>
