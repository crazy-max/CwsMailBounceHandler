<?php

namespace SGT\MailBounceHandler;

use Cws\CwsDebug;
use SGT\MailBounceHandler\Models\Mail;
use SGT\MailBounceHandler\Models\Result;

class EmlFileHandler extends Handler
{
    public function __construct(CwsDebug $cwsDebug) {
        parent::__construct($cwsDebug);
    }

    /**
     * Open a folder containing eml files on your system.
     *
     * @param string $emlFolder : the eml folder
     *
     * @return bool
     */
    public function openEmlFolder($emlFolder)
    {
        $this->reset();

        $this->cwsDebug->titleH2('Mode openEmlFolder', CwsDebug::VERBOSE_SIMPLE);
        $this->openMode = self::OPEN_MODE_FILE;

        $this->emlFolder = self::formatUnixPath(rtrim(realpath($emlFolder), '/'));
        $this->cwsDebug->labelValue('Open folder', $this->emlFolder, CwsDebug::VERBOSE_SIMPLE);

        $handle = @opendir($this->emlFolder);
        if (!$handle) {
            $this->error = 'Cannot open the eml folder ' . $this->emlFolder;
            $this->cwsDebug->error($this->error);

            return false;
        }

        $nbFiles = 0;
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..' || !self::endWith($file, '.eml')) {
                continue;
            }
            $emlFilePath = $this->emlFolder . '/' . $file;
            $emlFile = self::getEmlFile($emlFilePath);
            if (!empty($emlFile)) {
                $this->emlFiles[] = $emlFile;
            }
            $nbFiles++;
        }
        closedir($handle);

        if (empty($this->emlFiles)) {
            $this->error = 'No eml file found in ' . $this->emlFolder;
            $this->cwsDebug->error($this->error);

            return false;
        } else {
            $this->cwsDebug->labelValue('Opened', count($this->emlFiles) . ' / ' . $nbFiles . ' files.',
                CwsDebug::VERBOSE_SIMPLE);

            return true;
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
        $this->cwsDebug->simple('Process <strong>delete ' . $cwsMbhMail->getType() . ' bounce</strong> message ' . $cwsMbhMail->getToken() . ' in folder ' . $this->emlFolder,
            CwsDebug::VERBOSE_DEBUG);

        return @unlink($this->emlFolder . '/' . $cwsMbhMail->getToken());
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
        $moveFolder = $this->emlFolder . '/' . self::SUFFIX_BOUNCES_MOVE;
        if (!is_dir($moveFolder)) {
            mkdir($moveFolder);
        }
        $this->cwsDebug->simple('Process <strong>move ' . $cwsMbhMail->getType() . '</strong> in ' . $moveFolder . ' folder',
            CwsDebug::VERBOSE_DEBUG);

        return rename($this->emlFolder . '/' . $cwsMbhMail->getToken(), $moveFolder . '/' . $cwsMbhMail->getToken());
    }

    /**
     * Get eml file content on your system.
     *
     * @param string $emlFilePath : the eml file path
     *
     * @return array
     */
    protected static function getEmlFile($emlFilePath)
    {
        set_time_limit(6000);

        if (!file_exists($emlFilePath)) {
            return null;
        }

        $content = @file_get_contents($emlFilePath, false,
            stream_context_create(array('http' => array('method' => 'GET'))));
        if (empty($content)) {
            return null;
        }

        return array(
            'name' => basename($emlFilePath),
            'content' => $content,
        );
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

        if (empty($this->emlFiles)) {
            $this->error = 'File(s) not opened';
            $this->cwsDebug->error($this->error);

            return false;
        }

        $this->enableMove = $this->enableMove && $this->isMoveProcessMode();

        // count mails
        $totalMails = $this->isMailboxOpenMode() ? imap_num_msg($this->mailboxHandler) : count($this->emlFiles);
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
        } elseif ($this->isMoveProcessMode()) {
            $this->cwsDebug->simple('Running in <strong>move mode</strong>.', CwsDebug::VERBOSE_SIMPLE);
        } elseif ($this->isDeleteProcessMode()) {
            $this->cwsDebug->simple('<strong>Processed messages will be deleted</strong> from mailbox.',
                CwsDebug::VERBOSE_SIMPLE);
        }

        // parsing mails
        foreach ($this->emlFiles as $file) {
            $this->cwsDebug->titleH3('Msg #' . $file['name'], CwsDebug::VERBOSE_REPORT);
            $cwsMbhResult->addMail($this->processMailParsing($file['name'], $file['content']));
        }

        // process mails
        foreach ($cwsMbhResult->getMails() as $cwsMbhMail) {
            /* @var $cwsMbhMail Mail */
            if ($cwsMbhMail->isProcessed()) {
                $cwsMbhResult->getCounter()->incrProcessed();
                if ($this->enableMove) {
                    $this->processMailMove($cwsMbhMail);
                    $cwsMbhResult->getCounter()->incrMoved();
                } elseif ($this->isDeleteProcessMode()) {
                    $this->processMailDelete($cwsMbhMail);
                    $cwsMbhResult->getCounter()->incrDeleted();
                }
            } else {
                $cwsMbhResult->getCounter()->incrUnprocessed();
                if ($this->purge && $this->isDeleteProcessMode()) {
                    $this->processMailDelete($cwsMbhMail);
                    $cwsMbhResult->getCounter()->incrDeleted();
                }
            }
        }

        $this->cwsDebug->titleH2('Ending processMails', CwsDebug::VERBOSE_SIMPLE);
        $this->cwsDebug->dump('Counter result', $cwsMbhResult->getCounter(), CwsDebug::VERBOSE_SIMPLE);
        $this->cwsDebug->dump('Full result', $cwsMbhResult, CwsDebug::VERBOSE_REPORT);

        return $cwsMbhResult;
    }
}