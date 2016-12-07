<?php

/**
 * Result.
 *
 * @author Cr@zy
 * @copyright 2013-2015, Cr@zy
 * @license GNU LESSER GENERAL PUBLIC LICENSE
 *
 * @link https://github.com/crazy-max/CwsMailBounceHandler
 */
namespace Cws\MailBounceHandler\Models;

class Result
{
    /**
     * Counter report.
     *
     * @var Counter
     */
    private $counter;

    /**
     * List of mails.
     *
     * @see Cws\MailBounceHandler\Models\Mail
     *
     * @var array
     */
    private $mails;

    public function __construct()
    {
        $this->counter = new Counter();
        $this->mails = array();
    }

    public function getCounter()
    {
        if ($this->counter instanceof Counter) {
            return $this->counter;
        }
        return null;
    }

    public function setCounter(Counter $counter)
    {
        $this->counter = $counter;
    }

    public function getMails()
    {
        return $this->mails;
    }

    public function addMail(Mail $mail)
    {
        $this->mails[] = $mail;
    }
}
