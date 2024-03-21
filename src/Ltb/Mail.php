<?php namespace Ltb;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * How the class should be used:
 *  - initialize the mailer with init_mailer_locally
 *  - use send_mail_global to send mails
 * 
 * For compatibility reasons, you can also use init_mailer_legacy (or init_mailer with 0 arguments) that will return the mailer.
 * The returned mailer can than be used to call send_mail directly, but this should also not be used!
 */
final class Mail {
    
    private static $mailer = null;

    # Mail functions
    
    # initializes the mailer
    static function init_mailer_locally(
            $mail_priority,
            $mail_charset,
            $mail_contenttype,
            $mail_wordwrap,
            $mail_sendmailpath,
            $mail_protocol,
            $mail_smtp_debug,
            $mail_debug_format,
            $mail_smtp_host,
            $mail_smtp_port,
            $mail_smtp_secure,
            $mail_smtp_autotls,
            $mail_smtp_auth,
            $mail_smtp_user,
            $mail_smtp_pass,
            $mail_smtp_keepalive,
            $mail_smtp_options,
            $mail_smtp_timeout
    )
    {

        #==============================================================================
        # Email Config
        #==============================================================================
        
        if (self::$mailer == null) {
            self::$mailer = new PHPMailer;
        }
        
        self::$mailer->Priority      = $mail_priority;
        self::$mailer->CharSet       = $mail_charset;
        self::$mailer->ContentType   = $mail_contenttype;
        self::$mailer->WordWrap      = $mail_wordwrap;
        self::$mailer->Sendmail      = $mail_sendmailpath;
        self::$mailer->Mailer        = $mail_protocol;
        self::$mailer->SMTPDebug     = $mail_smtp_debug;
        self::$mailer->Debugoutput   = $mail_debug_format;
        self::$mailer->Host          = $mail_smtp_host;
        self::$mailer->Port          = $mail_smtp_port;
        self::$mailer->SMTPSecure    = $mail_smtp_secure;
        self::$mailer->SMTPAutoTLS   = $mail_smtp_autotls;
        self::$mailer->SMTPAuth      = $mail_smtp_auth;
        self::$mailer->Username      = $mail_smtp_user;
        self::$mailer->Password      = $mail_smtp_pass;
        self::$mailer->SMTPKeepAlive = $mail_smtp_keepalive;
        self::$mailer->SMTPOptions   = $mail_smtp_options;
        self::$mailer->Timeout       = $mail_smtp_timeout;
    }

    /* @function boolean send_mail_gloabl(PHPMailer $mailer, string $mail, string $mail_from, string $subject, string $body, array $data)
     * Send a mail, replace strings in body
     *
     * use global PHPMailer $mailer, create one from mail_XXX configurations if needed.
     #
     * @param mail Destination
     * @param mail_from Sender
     * @param subject Subject
     * @param body Body
     * @param data Data for string replacement
     * @return result
     */
    static function send_mail_global($mail, $mail_from, $mail_from_name, $subject, $body, $data) {
        if (self::$mailer == null) {
            self::init_mailer_legacy();   # for compatibility
        }
        $mailer = self::$mailer;
        return self::send_mail($mailer, $mail, $mail_from, $mail_from_name, $subject, $body, $data);
    }

    /* @function boolean send_mail(PHPMailer $mailer, string $mail, string $mail_from, string $subject, string $body, array $data)
     * Send a mail, replace strings in body
     * Should not be used directly, use send_mail_global
     * @param mailer PHPMailer object
     * @param mail Destination or array of destinations.
     * @param mail_from Sender
     * @param subject Subject
     * @param body Body
     * @param data Data for string replacement
     * @return result
     */
    static function send_mail($mailer, $mail, $mail_from, $mail_from_name, $subject, $body, $data) {

        $result = false;

        if (!is_a($mailer, 'PHPMailer\PHPMailer\PHPMailer')) {
            error_log("send_mail: PHPMailer object required!");
            return $result;
        }

        if (!$mail) {
            error_log("send_mail: no mail given, exiting...");
            return $result;
        }

        /* Replace {$key} fields from data in mail, mail_fromn subject and body */
        foreach ($data as $key => $value ) {
            # remark $mail can be an array, this is supported by str_replace.
            $mail = str_replace('{'.$key.'}', $value, $mail);
            $mail_from = str_replace('{'.$key.'}', $value, $mail_from);
            $subject = str_replace('{'.$key.'}', $value, $subject);
            $body = str_replace('{'.$key.'}', $value, $body);
        }

        # if not done addAddress and addReplyTo are cumulated at each call
        $mailer->clearAddresses();
        $mailer->setFrom($mail_from, $mail_from_name);
        $mailer->addReplyTo($mail_from, $mail_from_name);
        # support list of mails
        if ( is_array($mail) ) {
            foreach( $mail as $mailstr ) {
                $mailer->addAddress($mailstr);
            }
        }
        else {
            $mailer->addAddress($mail);
        }
        $mailer->Subject = $subject;
        $mailer->Body = $body;

        $result = $mailer->send();

        if (!$result) {
            error_log("send_mail: ".$mailer->ErrorInfo);
        }

        return $result;

    }
    
    # To provide compatibility
    static function __callStatic($name, $arguments) {
        $count = count($arguments);
        if ($name == "init_mailer") {
            if ($count == 0) {
                return self::init_mailer_legacy();
            } else {
                return self::init_mailer_locally(extract($arguments));
            }
        }
    }
    
    
    # rely on global mail configured with $mail_XXXX variables

    # init $mailer from config $mail_XXXX variables
    # legacy code to be compliant with self-service-password existing configuration
    
    # better use init_mailer_locally directly!
    static function init_mailer_legacy() {
        global $mailer;
        global $mail_priority,  $mail_charset, $mail_contenttype,  $mail_wordwrap, $mail_sendmailpath;
        global $mail_protocol,  $mail_smtp_debug, $mail_debug_format, $mail_smtp_host, $mail_smtp_port;
        global $mail_smtp_secure, $mail_smtp_autotls, $mail_smtp_auth, $mail_smtp_user, $mail_smtp_pass;
        global $mail_smtp_keepalive, $mail_smtp_options, $mail_smtp_timeout;
        
        self::init_mailer_locally($mail_priority, $mail_charset, $mail_contenttype, $mail_wordwrap, $mail_sendmailpath, $mail_protocol, $mail_smtp_debug, $mail_debug_format, $mail_smtp_host, $mail_smtp_port, $mail_smtp_secure, $mail_smtp_autotls, $mail_smtp_auth, $mail_smtp_user, $mail_smtp_pass, $mail_smtp_keepalive, $mail_smtp_options, $mail_smtp_timeout);
        return self::$mailer;
    }

}
