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

class Recipient
{
    /**
     * The DSN action (only for DSN process)
     * @var string
     */
    private $action;
    
    /**
     * The status code
     * @var string
     */
    private $status;
    
    /**
     * The recipient email
     * @var string
     */
    private $email;
    
    /**
     * Bounce type (see BOUNCE_ const in Cws\MailBounceHandler\Handler)
     * @var string
     */
    private $bounceType;
    
    /**
     * Bounce category (see CAT_ const in Cws\MailBounceHandler\Handler)
     * @var string
     */
    private $bounceCat;
    
    /**
     * To remove
     * @var boolean
     */
    private $remove;
    
    public function __construct()
    {
        $this->action = null;
        $this->status = null;
        $this->email = null;
        $this->bounceCat = null;
        $this->bounceCat = \Cws\MailBounceHandler\Handler::CAT_UNRECOGNIZED;
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
