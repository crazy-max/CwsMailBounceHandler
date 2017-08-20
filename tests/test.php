<?php

require_once __DIR__.'/../vendor/autoload.php'; // Autoload files using Composer autoload

$cwsDebug = new Cws\CwsDebug();
$cwsDebug->setDebugVerbose();
$cwsDebug->setEchoMode();

$sgtMbh = new SGT\MailBounceHandler\EmlFileHandler($cwsDebug);

// process mode
$sgtMbh->setNeutralProcessMode(); // default
//$sgtMbh->setMoveProcessMode();
//$sgtMbh->setDeleteProcessMode();

/*
 * Eml folder
 */
if ($sgtMbh->openEmlFolder(__DIR__.'/emls') === false) {
    $error = $sgtMbh->getError();

    return;
}

/*
 * Local mailbox
 */
 /*
$sgtMbh = new SGT\MailBounceHandler\ImapHandler($cwsDebug);

if ($sgtMbh->openLocal('/home/email/temp/mailbox') === false) {
    $error = $sgtMbh->getError();
    return;
}*/

/*
 * Remote mailbox
 */
/*
$sgtMbh = new SGT\MailBounceHandler\ImapHandler($cwsDebug);
$sgtMbh->setMailboxHost('imap.mydomain.com'); // default 'localhost'
$sgtMbh->setMailboxPort(993); // default const MAILBOX_PORT_IMAP
$sgtMbh->setMailboxUsername('myusername');
$sgtMbh->setMailboxPassword('mypassword');
$sgtMbh->setMailboxSecurity(CwsMailBounceHandler::MAILBOX_SECURITY_SSL); // default const MAILBOX_SECURITY_NOTLS
$sgtMbh->setMailboxCertValidate(); // default const MAILBOX_CERT_NOVALIDATE
$sgtMbh->setMailboxName('SPAM'); // default 'INBOX'
if ($sgtMbh->openImapRemote() === false) {
    $error = $sgtMbh->getError();
    return;
}*/

// process mails!
$result = $sgtMbh->processMails();
if (!$result instanceof \SGT\MailBounceHandler\Models\Result) {
    $error = $sgtMbh->getError();
} else {
    // continue with Result
    $counter = $result->getCounter();
    echo '<h2>Counter</h2>';
    echo 'total : '.$counter->getTotal().'<br />';
    echo 'fetched : '.$counter->getFetched().'<br />';
    echo 'processed : '.$counter->getProcessed().'<br />';
    echo 'unprocessed : '.$counter->getUnprocessed().'<br />';
    echo 'deleted : '.$counter->getDeleted().'<br />';
    echo 'moved : '.$counter->getMoved().'<br />';

    $mails = $result->getMails();
    echo '<h2>Mails</h2>';
    foreach ($mails as $mail) {
        if (!$mail instanceof \SGT\MailBounceHandler\Models\Mail) {
            continue;
        }
        echo '<h3>'.$mail->getToken().'</h3>';
        echo 'subject : '.$mail->getSubject().'<br />';
        echo 'type : '.$mail->getType().'<br />';
        echo 'recipients :<br />';
        foreach ($mail->getRecipients() as $recipient) {
            if (!$recipient instanceof \SGT\MailBounceHandler\Models\Recipient) {
                continue;
            }
            echo '- '.$recipient->getEmail().'<br />';
        }
    }
}
