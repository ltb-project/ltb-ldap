<?php namespace Ltb;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Mail extends PHPMailer{

    # Mail functions
    function __construct( $mail_priority,
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
        parent::__construct();

        $this->Priority      = $mail_priority;
        $this->CharSet       = $mail_charset;
        $this->ContentType   = $mail_contenttype;
        $this->WordWrap      = $mail_wordwrap;
        $this->Sendmail      = $mail_sendmailpath;
        $this->Mailer        = $mail_protocol;
        $this->SMTPDebug     = $mail_smtp_debug;
        $this->Debugoutput   = $mail_debug_format;
        $this->Host          = $mail_smtp_host;
        $this->Port          = $mail_smtp_port;
        $this->SMTPSecure    = $mail_smtp_secure;
        $this->SMTPAutoTLS   = $mail_smtp_autotls;
        $this->SMTPAuth      = $mail_smtp_auth;
        $this->Username      = $mail_smtp_user;
        $this->Password      = $mail_smtp_pass;
        $this->SMTPKeepAlive = $mail_smtp_keepalive;
        $this->SMTPOptions   = $mail_smtp_options;
        $this->Timeout       = $mail_smtp_timeout;
 
    }

    /* @function boolean send_mail(string $mail, string $mail_from, string $subject, string $body, array $data, string $html_body)
     * Send a mail, replace strings in body
     * @param mail Destination or array of destinations.
     * @param mail_from Sender
     * @param subject Subject
     * @param body Body
     * @param data Data for string replacement
     * @param html_body HTML body
     * @return result
     */
    public function send_mail($mail, $mail_from, $mail_from_name, $subject, $body, $data, $html_body = NULL) {

        $result = false;

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
        $this->clearAddresses();
        $this->setFrom($mail_from, $mail_from_name);
        $this->addReplyTo($mail_from, $mail_from_name);
        # support list of mails
        if ( is_array($mail) ) {
            foreach( $mail as $mailstr ) {
                $this->addAddress($mailstr);
            }
        }
        else {
            $this->addAddress($mail);
        }
        $this->Subject = $subject;

        if ($html_body) {
            $this->Body = $html_body;
            $this->AltBody = $body;
        } else {
            $this->Body = $body;
        }

        $result = $this->send();

        if (!$result) {
            error_log("send_mail: ".$this->ErrorInfo);
        }

        return $result;

    }

}
