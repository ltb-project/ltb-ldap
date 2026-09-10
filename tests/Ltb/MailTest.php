<?php

require __DIR__ . '/../../vendor/autoload.php';


final class MailTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{


    public function test_constructor(): void
    {
        
        $mail_priority = 3; // Options: null (default), 1 = High, 3 = Normal, 5 = low. When null, the header is not set at all.
        $mail_charset = 'utf-8';
        $mail_contenttype = 'text/plain';
        $mail_wordwrap = 0;
        $mail_sendmailpath = '/usr/sbin/sendmail';
        $mail_protocol = 'smtp';
        $mail_smtp_debug = 0;
        $mail_debug_format = 'error_log';
        $mail_smtp_host = '127.0.0.1';
        $mail_smtp_port = '25';
        $mail_smtp_secure = false;
        $mail_smtp_autotls = false;
        $mail_smtp_auth = false;
        $mail_smtp_user = '';
        $mail_smtp_pass = '';
        $mail_smtp_keepalive = false;
        $mail_smtp_options = array();
        $mail_smtp_timeout = 30;

        $mailer = new \Ltb\Mail( $mail_priority,
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
                              );

        $this->assertEquals($mail_priority, $mailer->Priority, "Error while setting mail_priority");
        $this->assertEquals($mail_charset, $mailer->CharSet, "Error while setting mail_charset");
        $this->assertEquals($mail_contenttype, $mailer->ContentType, "Error while setting mail_contenttype");
        $this->assertEquals($mail_wordwrap, $mailer->WordWrap, "Error while setting mail_wordwrap");
        $this->assertEquals($mail_sendmailpath, $mailer->Sendmail, "Error while setting mail_sendmailpath");
        $this->assertEquals($mail_protocol, $mailer->Mailer, "Error while setting mail_protocol");
        $this->assertEquals($mail_smtp_debug, $mailer->SMTPDebug, "Error while setting mail_smtp_debug");
        $this->assertEquals($mail_debug_format, $mailer->Debugoutput, "Error while setting mail_debug_format");
        $this->assertEquals($mail_smtp_host, $mailer->Host, "Error while setting mail_smtp_host");
        $this->assertEquals($mail_smtp_port, $mailer->Port, "Error while setting mail_smtp_port");
        $this->assertEquals($mail_smtp_secure, $mailer->SMTPSecure, "Error while setting mail_smtp_secure");
        $this->assertEquals($mail_smtp_autotls, $mailer->SMTPAutoTLS, "Error while setting mail_smtp_autotls");
        $this->assertEquals($mail_smtp_auth, $mailer->SMTPAuth, "Error while setting mail_smtp_auth");
        $this->assertEquals($mail_smtp_user, $mailer->Username, "Error while setting mail_smtp_user");
        $this->assertEquals($mail_smtp_pass, $mailer->Password, "Error while setting mail_smtp_pass");
        $this->assertEquals($mail_smtp_keepalive, $mailer->SMTPKeepAlive, "Error while setting mail_smtp_keepalive");
        $this->assertEquals($mail_smtp_options, $mailer->SMTPOptions, "Error while setting mail_smtp_options");
        $this->assertEquals($mail_smtp_timeout, $mailer->Timeout, "Error while setting mail_smtp_timeout");

    }

    public function test_send_mail(): void
    {

        $mail_from = "{mail_from}";
        $mail_from_name = "ltb admin sender";
        $mail_signature = "";
        $mail = ['{mail_to}','ltbadmin@domain.com'];
        $data = [
                  'mail_from' => 'ltbadminsender@example.com',
                  "login" => 'ltbtest',
                  "mail_to" => 'ltbtest@domain.com',
                  "password" => 'secret'
                ];
        $subject = 'Mail test to {login}';
        $body = 'Hello {login}, this is a mail test from {mail_from}. Your new password is {password}';

        $mailerMock = Mockery::mock('\Ltb\Mail')->makePartial();

        $mailerMock->shouldreceive('clearAddresses')
                   ->andReturn(true);

        $mailerMock->shouldreceive('setFrom')
                   ->with('ltbadminsender@example.com', 'ltb admin sender')
                   ->andReturn(true);

        $mailerMock->shouldreceive('addReplyTo')
                   ->with('ltbadminsender@example.com', 'ltb admin sender')
                   ->andReturn(true);

        $mailerMock->shouldreceive('addAddress')
                   ->with(Mockery::anyOf('ltbtest@domain.com', 'ltbadmin@domain.com'))
                   ->andReturn(true);

        $mailerMock->shouldreceive('send')
                   ->andReturn(true);

        $mailerMock->__construct(null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null);

        $result = $mailerMock->send_mail($mail, $mail_from, $mail_from_name, $subject, $body, $data);

        $this->assertEquals('Mail test to ltbtest', $mailerMock->Subject, "Error while processing subject");
        $this->assertEquals('Hello ltbtest, this is a mail test from ltbadminsender@example.com. Your new password is secret', $mailerMock->Body, "Error while processing body");
        $this->assertNotFalse($result, "Error in send() result");
    }

    public function test_send_mail_not_initialized(): void
    {

        $mail_from = "{mail_from}";
        $mail_from_name = "ltb admin sender";
        $mail_signature = "";
        $mail = null;
        $data = [
                  'mail_from' => 'ltbadminsender@example.com',
                  "login" => 'ltbtest',
                  "mail_to" => 'ltbtest@domain.com',
                  "password" => 'secret'
                ];
        $subject = 'Mail test to {login}';
        $body = 'Hello {login}, this is a mail test from {mail_from}. Your new password is {password}';

        $mailer = new \Ltb\Mail(null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null);

        $result = $mailer->send_mail($mail, $mail_from, $mail_from_name, $subject, $body, $data);

        $this->assertFalse($result, "Unexpected 'not false' send() result");
    }

    public function test_send_mail_html(): void
    {

        $mail_from = "{mail_from}";
        $mail_from_name = "ltb admin sender";
        $mail_signature = "";
        $mail = ['{mail_to}','ltbadmin@domain.com'];
        $data = [
                  'mail_from' => 'ltbadminsender@example.com',
                  "login" => 'ltbtest',
                  "mail_to" => 'ltbtest@domain.com',
                  "password" => 'secret'
                ];
        $subject = 'Mail test to {login}';
        $body = 'Hello {login}, this is a mail test from {mail_from}. Your new password is {password}';
        $html_body = '<html><body><p>Hello, this is a mail test</p></body></html>';

        $mailerMock = Mockery::mock('\Ltb\Mail')->makePartial();

        $mailerMock->shouldreceive('clearAddresses')
                   ->andReturn(true);

        $mailerMock->shouldreceive('setFrom')
                   ->with('ltbadminsender@example.com', 'ltb admin sender')
                   ->andReturn(true);

        $mailerMock->shouldreceive('addReplyTo')
                   ->with('ltbadminsender@example.com', 'ltb admin sender')
                   ->andReturn(true);

        $mailerMock->shouldreceive('addAddress')
                   ->with(Mockery::anyOf('ltbtest@domain.com', 'ltbadmin@domain.com'))
                   ->andReturn(true);

        $mailerMock->shouldreceive('send')
                   ->andReturn(true);

        $mailerMock->__construct(null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null);

        $result = $mailerMock->send_mail($mail, $mail_from, $mail_from_name, $subject, $body, $data, $html_body);

        $this->assertEquals('Mail test to ltbtest', $mailerMock->Subject, "Error while processing subject");
        $this->assertEquals('<html><body><p>Hello, this is a mail test</p></body></html>', $mailerMock->Body, "Error while processing body");
        $this->assertEquals('Hello ltbtest, this is a mail test from ltbadminsender@example.com. Your new password is secret', $mailerMock->AltBody, "Error while processing alt body");
        $this->assertNotFalse($result, "Error in send() result");
    }
}
