<?php

include('class.cws.bouncemailhandler.php');

// example
$cwsBounceMailHandler = new CwsBounceMailHandler();
$cwsBounceMailHandler->test_mode            = true; // test mode, if true will not delete messages ; default true
$cwsBounceMailHandler->debug_verbose        = CWSBMH_VERBOSE_DEBUG; // Control the debug output ; default is CWSBMH_VERBOSE_SIMPLE
//$cwsBounceMailHandler->use_fetchstructure = true; // control the method to process the mail header ; default true
//$cwsBounceMailHandler->purge              = false; // purge unknown messages ; default false
//$cwsBounceMailHandler->disable_delete     = false; // if disable_delete is equal to true, it will disable the delete function ; default false

/**
 * Local mailbox
 */
//if ($cwsBounceMailHandler->openLocal('/home/email/temp/mailbox')) {
//    $cwsBounceMailHandler->processMailbox();
//    var_dump($cwsBounceMailHandler->result);
//}

/**
 * Remote mailbox
 */
$cwsBounceMailHandler->host               = ''; // Mail host server ; default 'localhost'
$cwsBounceMailHandler->username           = ''; // Mailbox username
$cwsBounceMailHandler->password           = ''; // Mailbox password
//$cwsBounceMailHandler->port             = 143; // the port to access your mailbox ; default 143, other common choices are 110 (pop3), 995 (gmail)
//$cwsBounceMailHandler->service          = 'imap'; // the service to use (imap or pop3) ; default 'imap'
//$cwsBounceMailHandler->service_option   = 'notls'; // the service options (none, tls, notls, ssl) ; default 'notls'
//$cwsBounceMailHandler->cert             = CWSBMH_CERT_NOVALIDATE; // certificates validation (CWSBMH_CERT_VALIDATE ou CWSBMH_CERT_NOVALIDATE) if service_option is 'tls' or 'ssl' ; default CWSBMH_CERT_NOVALIDATE
//$cwsBounceMailHandler->boxname          = 'INBOX'; // the mailbox to access ; default 'INBOX'
//$cwsBounceMailHandler->moveHard         = false; // default false
//$cwsBounceMailHandler->hardMailbox      = 'INBOX.hard'; // default 'INBOX.hard' - NOTE: must start with 'INBOX.'
//$cwsBounceMailHandler->moveSoft         = false; // default false
//$cwsBounceMailHandler->softMailbox      = 'INBOX.soft'; // default is 'INBOX.soft' - NOTE: must start with 'INBOX.'

if ($cwsBounceMailHandler->openRemote()) {
    $cwsBounceMailHandler->processMailbox();
    var_dump($cwsBounceMailHandler->result);
}

?>