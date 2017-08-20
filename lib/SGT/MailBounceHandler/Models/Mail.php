<?php

/**
 * Mail.
 *
 * @author Cr@zy, Seracid
 * @copyright 2013-2015, Cr@zy
 * @copyright 2017, Seracid
 * @license GNU LESSER GENERAL PUBLIC LICENSE
 *
 * @link https://github.com/seracid/CwsMailBounceHandler
 */

namespace SGT\MailBounceHandler\Models;

class Mail
{
    /**
     * Message number or filename.
     *
     * @var int|string
     */
    protected $token;

    /**
     * Was processed during bounce or fbl analyze.
     *
     * @var bool
     */
    protected $processed;

    /**
     * Message subject.
     *
     * @var string
     */
    protected $subject;

    /**
     * Message headers.
     *
     * @var object
     */
    protected $header;

    /**
     * Message body.
     *
     * @var object
     */
    protected $body;

    /**
     * Type detected (bounce or fbl).
     *
     * @var string
     */
    protected $type;

    /**
     * List of recipients,.
     *
     * @see Recipient
     *
     * @var array
     */
    protected $recipients;

    public function __construct()
    {
        $this->token = null;
        $this->processed = true;
        $this->subject = null;
        $this->type = null;
        $this->recipients = array();
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function isProcessed()
    {
        return $this->processed;
    }

    public function setProcessed($processed)
    {
        $this->processed = $processed;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setHeader($header)
    {
        $this->header = $header;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getRecipients()
    {
        return $this->recipients;
    }

    public function addRecipient(Recipient $recipient)
    {
        $this->recipients[] = $recipient;
    }
}
