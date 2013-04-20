<?php

include('class.cws.mbh.php');

$cwsMailBounceHandler = new CwsMailBounceHandler();
$cwsMailBounceHandler->test_mode            = true;                  // default false
$cwsMailBounceHandler->debug_verbose        = CWSMBH_VERBOSE_DEBUG;  // default CWSMBH_VERBOSE_SIMPLE
//$cwsMailBounceHandler->purge              = false;                 // default false
//$cwsMailBounceHandler->disable_delete     = false;                 // default false
//$cwsMailBounceHandler->open_mode          = CWSMBH_OPEN_MODE_IMAP; // default CWSMBH_OPEN_MODE_IMAP
//$cwsMailBounceHandler->move_soft          = false;                 // default false
//$cwsMailBounceHandler->folder_soft        = 'INBOX.soft';          // default 'INBOX.hard' - NOTE: for open_mode IMAP it must start with 'INBOX.'
//$cwsMailBounceHandler->move_hard          = false;                 // default false
//$cwsMailBounceHandler->folder_hard        = 'INBOX.hard';          // default 'INBOX.soft' - NOTE: for open_mode IMAP it must start with 'INBOX.'

/**
 * .eml folder
 */
//$cwsMailBounceHandler->open_mode = CWSMBH_OPEN_MODE_FILE;
//if ($cwsMailBounceHandler->openFolder('emls/')) {
    //$cwsMailBounceHandler->processMails();
//}

/**
 * .eml file
 */
//$cwsMailBounceHandler->open_mode = CWSMBH_OPEN_MODE_FILE;
//if ($cwsMailBounceHandler->openFile('test/01.eml')) {
//    $cwsMailBounceHandler->processMails();
//}

/**
 * Local mailbox
 */
//$cwsMailBounceHandler->open_mode     = CWSMBH_OPEN_MODE_IMAP;
//if ($cwsMailBounceHandler->openImapLocal('/home/email/temp/mailbox')) {
//    $cwsMailBounceHandler->processMails();
//}

/**
 * Remote mailbox
 */
$cwsMailBounceHandler->open_mode          = CWSMBH_OPEN_MODE_IMAP;
$cwsMailBounceHandler->host               = '';                     // Mail host server ; default 'localhost'
$cwsMailBounceHandler->username           = '';                     // Mailbox username
$cwsMailBounceHandler->password           = '';                     // Mailbox password
//$cwsMailBounceHandler->port             = 143;                    // the port to access your mailbox ; default 143, other common choices are 110 (pop3), 995 (gmail)
//$cwsMailBounceHandler->service          = 'imap';                 // the service to use (imap or pop3) ; default 'imap'
//$cwsMailBounceHandler->service_option   = 'notls';                // the service options (none, tls, notls, ssl) ; default 'notls'
//$cwsMailBounceHandler->cert             = CWSMBH_CERT_NOVALIDATE; // certificates validation (CWSMBH_CERT_VALIDATE or CWSMBH_CERT_NOVALIDATE) if service_option is 'tls' or 'ssl' ; default CWSMBH_CERT_NOVALIDATE
//$cwsMailBounceHandler->boxname          = 'TEST';                 // the mailbox to access ; default 'INBOX'

if ($cwsMailBounceHandler->openImapRemote()) {
    $cwsMailBounceHandler->processMails();
}

?>