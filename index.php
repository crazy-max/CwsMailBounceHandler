<?php

include('class.cws.mailbouncehandler.php');

// example
$cwsMailBounceHandler = new CwsMailBounceHandler();
$cwsMailBounceHandler->test_mode            = true; // test mode, if true will not delete messages ; default true
$cwsMailBounceHandler->debug_verbose        = CWSMBH_VERBOSE_DEBUG; // Control the debug output ; default is CWSMBH_VERBOSE_SIMPLE
//$cwsMailBounceHandler->use_fetchstructure = true; // control the method to process the mail header ; default true
//$cwsMailBounceHandler->purge              = false; // purge unknown messages ; default false
//$cwsMailBounceHandler->disable_delete     = false; // if disable_delete is equal to true, it will disable the delete function ; default false

/**
 * Local mailbox
 */
//if ($cwsMailBounceHandler->openLocal('/home/email/temp/mailbox')) {
//    $cwsMailBounceHandler->processMailbox();
//    var_dump($cwsMailBounceHandler->result);
//}

/**
 * Remote mailbox
 */
$cwsMailBounceHandler->host               = ''; // Mail host server ; default 'localhost'
$cwsMailBounceHandler->username           = ''; // Mailbox username
$cwsMailBounceHandler->password           = ''; // Mailbox password
//$cwsMailBounceHandler->port             = 143; // the port to access your mailbox ; default 143, other common choices are 110 (pop3), 995 (gmail)
//$cwsMailBounceHandler->service          = 'imap'; // the service to use (imap or pop3) ; default 'imap'
//$cwsMailBounceHandler->service_option   = 'notls'; // the service options (none, tls, notls, ssl) ; default 'notls'
//$cwsMailBounceHandler->cert             = CWSMBH_CERT_NOVALIDATE; // certificates validation (CWSMBH_CERT_VALIDATE ou CWSMBH_CERT_NOVALIDATE) if service_option is 'tls' or 'ssl' ; default CWSMBH_CERT_NOVALIDATE
//$cwsMailBounceHandler->boxname          = 'INBOX'; // the mailbox to access ; default 'INBOX'
//$cwsMailBounceHandler->moveHard         = false; // default false
//$cwsMailBounceHandler->hardMailbox      = 'INBOX.hard'; // default 'INBOX.hard' - NOTE: must start with 'INBOX.'
//$cwsMailBounceHandler->moveSoft         = false; // default false
//$cwsMailBounceHandler->softMailbox      = 'INBOX.soft'; // default is 'INBOX.soft' - NOTE: must start with 'INBOX.'

if ($cwsMailBounceHandler->openRemote()) {
    $cwsMailBounceHandler->processMailbox();
    var_dump($cwsMailBounceHandler->result);
}

?>