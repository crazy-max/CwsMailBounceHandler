<?php

/**
 * Mail.
 *
 * @author Cr@zy
 * @copyright 2013-2015, Cr@zy
 * @license GNU LESSER GENERAL PUBLIC LICENSE
 *
 * @link https://github.com/crazy-max/CwsMailBounceHandler
 */
namespace Cws\MailBounceHandler\Models;

class Mail
{
    /**
     * Message number or filename.
     *
     * @var int|string
     */
    private $token;

    /**
     * Was processed during bounce or fbl analyze.
     *
     * @var bool
     */
    private $processed;

    /**
     * Message subject.
     *
     * @var string
     */
    private $subject;

    /**
     * Message headers.
     *
     * @var object
     */
    private $header;

    /**
     * Message body.
     *
     * @var object
     */
    private $body;

    /**
     * Type detected (bounce or fbl).
     *
     * @var string
     */
    private $type;

    /**
     * List of recipients,.
     *
     * @see Cws\MailBounceHandler\Models\Recipient object
     *
     * @var array
     */
    private $recipients;

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
