<?php

namespace Cws\MailBounceHandler\Models;

/**
 * CwsMailBounceHandler
 *
 * CwsMailBounceHandler is a PHP class to help webmasters handle bounce-back,
 * feedback loop and ARF mails in standard DSN (Delivery Status Notification, RFC-1894).
 * It checks your IMAP/POP3 inbox or eml files and delete or move all 'hard' bounced emails.
 * If a bounce is malformed, it tries to extract some useful information to parse status.
 * A result array is available to process custom post-actions.
 * 
 * CwsMailBounceHandler is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * CwsMailBounceHandler is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/.
 * 
 * Related post: http://goo.gl/Wrq8J
 *
 * @package CwsMailBounceHandler
 * @author Cr@zy
 * @copyright 2013-2015, Cr@zy
 * @license GNU LESSER GENERAL PUBLIC LICENSE
 * @version 1.6
 * @link https://github.com/crazy-max/CwsMailBounceHandler
 *
 */
class Mail {

    /**
     * Message number or filename
     * @var int|string
     */
    private $token;

    /**
     * Was processed during bounce or fbl analyze
     * @var boolean
     */
    private $processed;

    /**
     * Message subject
     * @var string
     */
    private $subject;

    /**
     * Type detected (bounce or fbl)
     * @var string
     */
    private $type;

    /**
     * List of recipients, 
     * @see Cws\MailBounceHandler\Models\Recipient object
     * @var array
     */
    private $recipients;

    public function __construct() {
        $this->token = null;
        $this->processed = true;
        $this->subject = null;
        $this->type = null;
        $this->recipients = array();
    }

    public function getToken() {
        return $this->token;
    }

    public function setToken($token) {
        $this->token = $token;
    }

    public function isProcessed() {
        return $this->processed;
    }

    public function setProcessed($processed) {
        $this->processed = $processed;
    }

    public function getSubject() {
        return $this->subject;
    }

    public function setSubject($subject) {
        $this->subject = $subject;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getRecipients() {
        return $this->recipients;
    }

    public function addRecipient(Recipient $recipient) {
        $this->recipients[] = $recipient;
    }

}
