<?php

namespace SGT\MailBounceHandler;

use Exception;
use Cws\CwsDebug;
use SGT\MailBounceHandler\Models\Mail;
use SGT\MailBounceHandler\Models\Result;

class ImapHandler extends Handler
{

    public function __construct(CwsDebug $cwsDebug)
    {
        parent::__construct($cwsDebug);
        $this->mailboxService = self::MAILBOX_SERVICE_IMAP;
        $this->mailboxHost = 'localhost';
        $this->mailboxPort = self::MAILBOX_PORT_IMAP;
        $this->mailboxSecurity = self::MAILBOX_SECURITY_NOTLS;
        $this->mailboxCert = self::MAILBOX_CERT_NOVALIDATE;
    }

    /**
     * Open a IMAP mail box in local file system.
     *
     * @param string $filePath : the local mailbox file path
     *
     * @return bool
     */
    public function openLocal($filePath)
    {
        $this->reset();

        $this->cwsDebug->titleH2('Mode openImapLocal', CwsDebug::VERBOSE_SIMPLE);
        $this->openMode = self::OPEN_MODE_MAILBOX;

        $this->mailboxHandler = imap_open($filePath, '', '', !$this->isNeutralProcessMode() ? CL_EXPUNGE : null);

        if (!$this->mailboxHandler) {
            $this->error = 'Cannot open the mailbox file to ' . $filePath . ': ' . imap_last_error();
            $this->cwsDebug->error($this->error);

            return false;
        }

        $this->cwsDebug->labelValue('Opened', $filePath, CwsDebug::VERBOSE_SIMPLE);
        return true;
    }

    /**
     * Open a remote IMAP mail box.
     *
     * @return bool
     */
    public function openRemote()
    {
        try {
            $this->reset();

            $this->cwsDebug->titleH2('Mode openImapRemote', CwsDebug::VERBOSE_SIMPLE);
            $this->openMode = self::OPEN_MODE_MAILBOX;

            // disable move operations if server is Gmail... Gmail does not support mailbox creation
            if (stristr($this->mailboxHost, 'gmail') && $this->isMoveProcessMode()) {
                $this->enableMove = false;
                $this->cwsDebug->simple('<strong>Move operations disabled</strong> for Gmail server, Gmail does not support mailbox creation',
                    CwsDebug::VERBOSE_SIMPLE);
            }

            // required options for imap_open connection.
            $opts = '/' . $this->mailboxService . '/' . $this->mailboxSecurity;
            if ($this->mailboxSecurity == self::MAILBOX_SECURITY_TLS || $this->mailboxSecurity == self::MAILBOX_SECURITY_SSL) {
                $opts .= '/' . $this->mailboxCert;
            }

            $this->mailboxHandler = imap_open('{' . $this->mailboxHost . ':' . $this->mailboxPort . $opts . '}' . $this->mailboxName,
                $this->mailboxUsername, $this->mailboxPassword, !$this->isNeutralProcessMode() ? CL_EXPUNGE : null);

            if (!$this->mailboxHandler) {
                $this->error = 'Cannot create ' . $this->mailboxService . ' connection to ' . $this->mailboxHost . ': ' . imap_last_error();
                $this->cwsDebug->error($this->error);

                return false;
            } else {
                $this->cwsDebug->labelValue('Connected to',
                    $this->mailboxHost . ':' . $this->mailboxPort . $opts . ' on mailbox ' . $this->mailboxName . ' (' . $this->mailboxUsername . ')',
                    CwsDebug::VERBOSE_SIMPLE);

                return true;
            }
        } catch (Exception $e) {
            $this->error = 'Cannot create ' . $this->mailboxService . ' connection to ' . $this->mailboxHost . ': ' . imap_last_error();
            $this->cwsDebug->error($this->error);

            return false;
        }
    }

    /**
     * Function to delete a message.
     *
     * @param Mail $cwsMbhMail : mail bounce object.
     *
     * @return bool
     */
    public function processMailDelete(Mail $cwsMbhMail)
    {
        $this->cwsDebug->simple('Process <strong>delete ' . $cwsMbhMail->getType() . ' bounce</strong> message ' . $cwsMbhMail->getToken() . ' in mailbox',
            CwsDebug::VERBOSE_DEBUG);

        return @imap_delete($this->mailboxHandler, $cwsMbhMail->getToken());
    }

    /**
     * Method to move a mail bounce.
     *
     * @param Mail $cwsMbhMail : mail bounce object.
     *
     * @return bool
     */
    public function processMailMove(Mail $cwsMbhMail)
    {
        $moveFolder = $this->getMailboxName() . '.' . self::SUFFIX_BOUNCES_MOVE;
        $this->cwsDebug->simple('Process <strong>move ' . $cwsMbhMail->getType() . '</strong> in ' . $moveFolder . ' mailbox',
            CwsDebug::VERBOSE_DEBUG);
        if ($this->isImapMailboxExists($moveFolder)) {
            return imap_mail_move($this->mailboxHandler, $cwsMbhMail->getToken(), $moveFolder);
        }
        return false;
    }

    /**
     * Function to check if a mailbox exists. If not found, it will create it.
     *
     * @param string $mailboxName : the mailbox name, must be in mailboxName.bounces format
     * @param bool $create : whether or not to create the mailbox if not found, defaults to true
     *
     * @return bool
     */
    protected function isImapMailboxExists($mailboxName, $create = true)
    {
        // required security option for imap_open connection.
        $opts = '/' . $this->mailboxService . '/' . $this->mailboxSecurity;
        if ($this->mailboxSecurity == self::MAILBOX_SECURITY_TLS || $this->mailboxSecurity == self::MAILBOX_SECURITY_SSL) {
            $opts .= '/' . $this->mailboxCert;
        }

        $handler = imap_open('{' . $this->mailboxHost . ':' . $this->mailboxPort . $opts . '}', $this->mailboxUsername,
            $this->mailboxPassword, !$this->isNeutralProcessMode() ? CL_EXPUNGE : null);

        $list = imap_getmailboxes($handler, '{' . $this->mailboxHost . ':' . $this->mailboxPort . $opts . '}', '*');

        $mailboxFound = false;
        if (is_array($list)) {
            foreach ($list as $val) {
                // get the mailbox name only
                $nameArr = explode('}', imap_utf7_decode($val->name));
                $nameRaw = $nameArr[count($nameArr) - 1];
                if ($mailboxName == $nameRaw) {
                    $mailboxFound = true;
                    break;
                }
            }
            if ($mailboxFound === false && $create) {
                $mailboxFound = @imap_createmailbox($handler,
                    imap_utf7_encode('{' . $this->mailboxHost . ':' . $this->mailboxPort . $opts . '}' . $mailboxName));
            }
        }

        return $mailboxFound;
    }

    /**
     * Process the messages in a mailbox or a folder.
     *
     * @return bool|Result
     */
    public function processMails()
    {
        $this->cwsDebug->titleH2('processMails', CwsDebug::VERBOSE_SIMPLE);
        $cwsMbhResult = new Result();

        if (!$this->mailboxHandler) {
            $this->error = 'Mailbox not opened';
            $this->cwsDebug->error($this->error);

            return false;
        }

        $this->enableMove = $this->enableMove && $this->isMoveProcessMode();

        // count mails
        $totalMails = imap_num_msg($this->mailboxHandler);
        $this->cwsDebug->labelValue('Total', $totalMails . ' messages', CwsDebug::VERBOSE_SIMPLE);

        // init counter
        $cwsMbhResult->getCounter()->setTotal($totalMails);
        $cwsMbhResult->getCounter()->setFetched($totalMails);

        // process maximum number of messages
        if ($this->maxMessages > 0 && $cwsMbhResult->getCounter()->getFetched() > $this->maxMessages) {
            $cwsMbhResult->getCounter()->setFetched($this->maxMessages);
            $this->cwsDebug->labelValue('Processing', $cwsMbhResult->getCounter()->getFetched() . ' messages',
                CwsDebug::VERBOSE_SIMPLE);
        }

        // check process mode
        if ($this->isNeutralProcessMode()) {
            $this->cwsDebug->simple('Running in <strong>neutral mode</strong>, messages will not be processed from mailbox.',
                CwsDebug::VERBOSE_SIMPLE);
            // parsing mails
            $this->parseMails($cwsMbhResult);
            // process mails
            foreach ($cwsMbhResult->getMails() as $cwsMbhMail) {
                /* @var $cwsMbhMail Mail */
                if ($cwsMbhMail->isProcessed()) {
                    $cwsMbhResult->getCounter()->incrProcessed();
                } else {
                    $cwsMbhResult->getCounter()->incrUnprocessed();
                }
            }
        } elseif ($this->isMoveProcessMode()) {
            $this->cwsDebug->simple('Running in <strong>move mode</strong>.', CwsDebug::VERBOSE_SIMPLE);
            // parsing mails
            $this->parseMails($cwsMbhResult);
            // process mails
            foreach ($cwsMbhResult->getMails() as $cwsMbhMail) {
                /* @var $cwsMbhMail Mail */
                if ($cwsMbhMail->isProcessed()) {
                    $cwsMbhResult->getCounter()->incrProcessed();
                    if ($this->enableMove) {
                        $this->processMailMove($cwsMbhMail);
                        $cwsMbhResult->getCounter()->incrMoved();
                    }
                } else {
                    $cwsMbhResult->getCounter()->incrUnprocessed();
                }
            }
        } elseif ($this->isDeleteProcessMode()) {
            $this->cwsDebug->simple('<strong>Processed messages will be deleted</strong> from mailbox.',
                CwsDebug::VERBOSE_SIMPLE);
            // parsing mails
            $this->parseMails($cwsMbhResult);
            // process mails
            foreach ($cwsMbhResult->getMails() as $cwsMbhMail) {
                /* @var $cwsMbhMail Mail */
                if ($cwsMbhMail->isProcessed()) {
                    $cwsMbhResult->getCounter()->incrProcessed();
                    $this->processMailDelete($cwsMbhMail);
                    $cwsMbhResult->getCounter()->incrDeleted();
                } else {
                    $cwsMbhResult->getCounter()->incrUnprocessed();
                    if ($this->purge) {
                        $this->processMailDelete($cwsMbhMail);
                        $cwsMbhResult->getCounter()->incrDeleted();
                    }
                }
            }
        }

        $this->cwsDebug->titleH2('Ending processMails', CwsDebug::VERBOSE_SIMPLE);
        $this->cwsDebug->dump('Counter result', $cwsMbhResult->getCounter(), CwsDebug::VERBOSE_SIMPLE);
        $this->cwsDebug->dump('Full result', $cwsMbhResult, CwsDebug::VERBOSE_REPORT);

        return $cwsMbhResult;
    }

    protected function parseMails(Result $cwsMbhResult) {
        for ($mailNo = 1; $mailNo <= $cwsMbhResult->getCounter()->getFetched(); $mailNo++) {
            $this->cwsDebug->titleH3('Msg #' . $mailNo, CwsDebug::VERBOSE_REPORT);
            $header = @imap_fetchheader($this->mailboxHandler, $mailNo);
            $body = @imap_body($this->mailboxHandler, $mailNo);
            $cwsMbhResult->addMail($this->processMailParsing($mailNo, $header . '\r\n\r\n' . $body));
        }
    }

    protected function closeMailbox()
    {
        $this->cwsDebug->simple('Closing mailbox, and purging messages', CwsDebug::VERBOSE_SIMPLE);
        return imap_close($this->mailboxHandler);
    }
}