<?php
// sample of cwsMailBounceHandler when installed with composer
// for now, you still need to manually install CwsDebug et CwsDump

// Download CwsDump at https://github.com/crazy-max/CwsDump
require_once '../CwsDump/class.cws.dump.php';
$cwsDump = new CwsDump();

// Download CwsDebug at https://github.com/crazy-max/CwsDebug
require_once '../CwsDebug/class.cws.debug.php';
$cwsDebug = new CwsDebug($cwsDump);
$cwsDebug->setDebugVerbose();
$cwsDebug->setEchoMode();

// load composer autoloader
require_once dirname(__FILE__).'/../../autoload.php';

$cwsMbh = new Cws\MailBounceHandler\Handler($cwsDebug);

// process mode
$cwsMbh->setNeutralProcessMode(); // default
//$cwsMbh->setMoveProcessMode();
//$cwsMbh->setDeleteProcessMode();

/**
 * Eml folder
 */
if ($cwsMbh->openEmlFolder('emls') === false) {
    $error = $cwsMbh->getError();
    return;
}

/**
 * Local mailbox
 */
/*if ($cwsMbh->openImapLocal('/home/email/temp/mailbox') === false) {
    $error = $cwsMbh->getError();
    return;
}*/

/**
 * Remote mailbox
 */
/*$cwsMbh->setImapMailboxService(); // default
$cwsMbh->setMailboxHost('imap.mydomain.com'); // default 'localhost'
$cwsMbh->setMailboxPort(993); // default const MAILBOX_PORT_IMAP
$cwsMbh->setMailboxUsername('myusername');
$cwsMbh->setMailboxPassword('mypassword');
$cwsMbh->setMailboxSecurity(Cws\MailBounceHandler\Handler::MAILBOX_SECURITY_SSL); // default const MAILBOX_SECURITY_NOTLS
$cwsMbh->setMailboxCertValidate(); // default const MAILBOX_CERT_NOVALIDATE
$cwsMbh->setMailboxName('SPAM'); // default 'INBOX'
if ($cwsMbh->openImapRemote() === false) {
    $error = $cwsMbh->getError();
    return;
}*/

// process mails!
$result = $cwsMbh->processMails();
if (!$result instanceof \Cws\MailBounceHandler\Models\Result) {
    $error = $cwsMbh->getError();
} else {
    // continue with CwsMbhResult
    
    $counter = $result->getCounter();
    echo '<h2>Counter</h2>';
    echo 'total : ' . $counter->getTotal() . '<br />';
    echo 'fetched : ' . $counter->getFetched() . '<br />';
    echo 'processed : ' . $counter->getProcessed() . '<br />';
    echo 'unprocessed : ' . $counter->getUnprocessed() . '<br />';
    echo 'deleted : ' . $counter->getDeleted() . '<br />';
    echo 'moved : ' . $counter->getMoved() . '<br />';
    
    $mails = $result->getMails();
    echo '<h2>Mails</h2>';
    foreach ($mails as $mail) {
        if (!$mail instanceof \Cws\MailBounceHandler\Models\Mail) {
            continue;
        }
        echo '<h3>' . $mail->getToken() . '</h3>';
        echo 'subject : ' . $mail->getSubject() . '<br />';
        echo 'type : ' . $mail->getType() . '<br />';
        echo 'recipients :<br />';
        foreach ($mail->getRecipients() as $recipient) {
            if (!$recipient instanceof \Cws\MailBounceHandler\Models\Recipient) {
                continue;
            }
            echo '- ' . $recipient->getEmail() . '<br />';
        }
    }
}
