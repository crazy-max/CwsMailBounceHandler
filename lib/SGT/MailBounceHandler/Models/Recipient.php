<?php

namespace SGT\MailBounceHandler\Models;

use SGT\MailBounceHandler\Handler;

/**
 * Recipient.
 *
 * @author Cr@zy
 * @copyright 2013-2016, Cr@zy
 * @license GNU LESSER GENERAL PUBLIC LICENSE
 *
 * @link https://github.com/seracid/CwsMailBounceHandler
 */
class Recipient
{
    /**
     * The DSN action (only for DSN process).
     *
     * @var string
     */
    protected $action;

    /**
     * The status code.
     *
     * @var string
     */
    protected $status;

    /**
     * The recipient email.
     *
     * @var string
     */
    protected $email;

    /**
     * Bounce type (see BOUNCE_ const in Cws\MailBounceHandler\Handler).
     *
     * @var string
     */
    protected $bounceType;

    /**
     * Bounce category (see CAT_ const in Cws\MailBounceHandler\Handler).
     *
     * @var string
     */
    protected $bounceCat;

    /**
     * To remove.
     *
     * @var bool
     */
    protected $remove;

    public function __construct()
    {
        $this->action = null;
        $this->status = null;
        $this->email = null;
        $this->bounceCat = null;
        $this->bounceCat = Handler::CAT_UNRECOGNIZED;
        $this->remove = false;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getBounceType()
    {
        return $this->bounceType;
    }

    public function setBounceType($bounceType)
    {
        $this->bounceType = $bounceType;
    }

    public function getBounceCat()
    {
        return $this->bounceCat;
    }

    public function setBounceCat($bounceCat)
    {
        $this->bounceCat = $bounceCat;
    }

    public function isRemove()
    {
        return $this->remove;
    }

    public function setRemove($remove)
    {
        $this->remove = $remove;
    }
}
