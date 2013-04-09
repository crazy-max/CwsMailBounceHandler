<?php

include('class.cws.bouncemail.php');

// example
$cwsBounceMail = new CwsBounceMail();
$cwsBounceMail->test_mode            = true; // test mode, if true will not delete messages ; default true
$cwsBounceMail->debug_verbose        = CWSBM_VERBOSE_DEBUG; // Control the debug output ; default is CWSBM_VERBOSE_SIMPLE
//$cwsBounceMail->use_fetchstructure = true; // control the method to process the mail header ; default true
//$cwsBounceMail->purge              = false; // purge unknown messages ; default false
//$cwsBounceMail->disable_delete     = false; // if disable_delete is equal to true, it will disable the delete function ; default false

/**
 * Local mailbox
 */
//if ($cwsBounceMail->openLocal('/home/email/temp/mailbox')) {
//    $cwsBounceMail->processMailbox();
//    var_dump($cwsBounceMail->result);
//}

/**
 * Remote mailbox
 */
$cwsBounceMail->host               = ''; // Mail host server ; default 'localhost'
$cwsBounceMail->username           = ''; // Mailbox username
$cwsBounceMail->password           = ''; // Mailbox password
//$cwsBounceMail->port             = 143; // the port to access your mailbox ; default 143, other common choices are 110 (pop3), 995 (gmail)
//$cwsBounceMail->service          = 'imap'; // the service to use (imap or pop3) ; default 'imap'
//$cwsBounceMail->service_option   = 'notls'; // the service options (none, tls, notls, ssl) ; default 'notls'
//$cwsBounceMail->cert             = CWSBM_CERT_NOVALIDATE; // certificates validation (CWSBM_CERT_VALIDATE ou CWSBM_CERT_NOVALIDATE) if service_option is 'tls' or 'ssl' ; default CWSBM_CERT_NOVALIDATE
//$cwsBounceMail->boxname          = 'INBOX'; // the mailbox to access ; default 'INBOX'
//$cwsBounceMail->moveHard         = false; // default false
//$cwsBounceMail->hardMailbox      = 'INBOX.hard'; // default 'INBOX.hard' - NOTE: must start with 'INBOX.'
//$cwsBounceMail->moveSoft         = false; // default false
//$cwsBounceMail->softMailbox      = 'INBOX.soft'; // default is 'INBOX.soft' - NOTE: must start with 'INBOX.'

if ($cwsBounceMail->openRemote()) {
    $cwsBounceMail->processMailbox();
    var_dump($cwsBounceMail->result);
}

?>