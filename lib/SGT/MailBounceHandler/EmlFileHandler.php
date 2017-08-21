<?php

namespace SGT\MailBounceHandler;

use Cws\CwsDebug;
use SGT\MailBounceHandler\Models\Mail;
use SGT\MailBounceHandler\Models\Result;

class EmlFileHandler extends Handler
{
    public function __construct(CwsDebug $cwsDebug)
    {
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
        }

        $this->cwsDebug->labelValue('Opened', count($this->emlFiles) . ' / ' . $nbFiles . ' files.',
            CwsDebug::VERBOSE_SIMPLE);

        return true;
    }

    /**
     * Function to delete a message.
     *
     * @param Mail $phpMbhMail : mail bounce object.
     *
     * @return bool
     */
    public function processMailDelete(Mail $phpMbhMail)
    {
        $this->cwsDebug->simple('Process <strong>delete ' . $phpMbhMail->getType() . ' bounce</strong> message ' . $phpMbhMail->getToken() . ' in folder ' . $this->emlFolder,
            CwsDebug::VERBOSE_DEBUG);

        return @unlink($this->emlFolder . '/' . $phpMbhMail->getToken());
    }

    /**
     * Method to move a mail bounce.
     *
     * @param Mail $phpMbhMail : mail bounce object.
     *
     * @return bool
     */
    public function processMailMove(Mail $phpMbhMail)
    {
        $moveFolder = $this->emlFolder . '/' . self::SUFFIX_BOUNCES_MOVE;
        if (!is_dir($moveFolder)) {
            mkdir($moveFolder);
        }
        $this->cwsDebug->simple('Process <strong>move ' . $phpMbhMail->getType() . '</strong> in ' . $moveFolder . ' folder',
            CwsDebug::VERBOSE_DEBUG);

        return rename($this->emlFolder . '/' . $phpMbhMail->getToken(), $moveFolder . '/' . $phpMbhMail->getToken());
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
        $phpMbhResult = new Result();

        if (empty($this->emlFiles)) {
            $this->error = 'File(s) not opened';
            $this->cwsDebug->error($this->error);

            return false;
        }

        $this->enableMove = $this->enableMove && $this->isMoveProcessMode();

        // count mails
        $totalMails = count($this->emlFiles);
        $this->cwsDebug->labelValue('Total', $totalMails . ' messages', CwsDebug::VERBOSE_SIMPLE);

        // init counter
        $phpMbhResult->getCounter()->setTotal($totalMails);
        $phpMbhResult->getCounter()->setFetched($totalMails);

        // process maximum number of messages
        if ($this->maxMessages > 0 && $phpMbhResult->getCounter()->getFetched() > $this->maxMessages) {
            $phpMbhResult->getCounter()->setFetched($this->maxMessages);
            $this->cwsDebug->labelValue('Processing', $phpMbhResult->getCounter()->getFetched() . ' messages',
                CwsDebug::VERBOSE_SIMPLE);
        }

        // check process mode
        if ($this->isNeutralProcessMode()) {
            $this->cwsDebug->simple('Running in <strong>neutral mode</strong>, messages will not be processed from mailbox.',
                CwsDebug::VERBOSE_SIMPLE);
            $this->parseMails($phpMbhResult);
            // process mails
            foreach ($phpMbhResult->getMails() as $phpMbhMail) {
                /* @var $phpMbhMail Mail */
                if ($phpMbhMail->isProcessed()) {
                    $phpMbhResult->getCounter()->incrProcessed();
                } else {
                    $phpMbhResult->getCounter()->incrUnprocessed();
                }
            }
        } elseif ($this->isMoveProcessMode()) {
            $this->cwsDebug->simple('Running in <strong>move mode</strong>.', CwsDebug::VERBOSE_SIMPLE);
            $this->parseMails($phpMbhResult);
            // process mails
            foreach ($phpMbhResult->getMails() as $phpMbhMail) {
                /* @var $phpMbhMail Mail */
                if ($phpMbhMail->isProcessed()) {
                    $phpMbhResult->getCounter()->incrProcessed();
                    if ($this->enableMove) {
                        $this->processMailMove($phpMbhMail);
                        $phpMbhResult->getCounter()->incrMoved();
                    }
                } else {
                    $phpMbhResult->getCounter()->incrUnprocessed();
                }
            }
        } elseif ($this->isDeleteProcessMode()) {
            $this->cwsDebug->simple('<strong>Processed messages will be deleted</strong> from mailbox.',
                CwsDebug::VERBOSE_SIMPLE);
            $this->parseMails($phpMbhResult);
            // process mails
            foreach ($phpMbhResult->getMails() as $phpMbhMail) {
                /* @var $phpMbhMail Mail */
                if ($phpMbhMail->isProcessed()) {
                    $phpMbhResult->getCounter()->incrProcessed();
                    $this->processMailDelete($phpMbhMail);
                    $phpMbhResult->getCounter()->incrDeleted();
                } else {
                    $phpMbhResult->getCounter()->incrUnprocessed();
                    if ($this->purge && $this->isDeleteProcessMode()) {
                        $this->processMailDelete($phpMbhMail);
                        $phpMbhResult->getCounter()->incrDeleted();
                    }
                }
            }
        }

        $this->cwsDebug->titleH2('Ending processMails', CwsDebug::VERBOSE_SIMPLE);
        $this->cwsDebug->dump('Counter result', $phpMbhResult->getCounter(), CwsDebug::VERBOSE_SIMPLE);
        $this->cwsDebug->dump('Full result', $phpMbhResult, CwsDebug::VERBOSE_REPORT);

        return $phpMbhResult;
    }

    /**
     * @param Result $phpMbhResult
     */
    protected function parseMails(Result $phpMbhResult)
    {
        foreach ($this->emlFiles as $file) {
            $this->cwsDebug->titleH3('Msg #' . $file['name'], CwsDebug::VERBOSE_REPORT);
            $phpMbhResult->addMail($this->processMailParsing($file['name'], $file['content']));
        }
    }
}