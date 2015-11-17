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

class Result
{
    /**
     * Counter report
     * @var Counter
     */
    private $counter;
    
    /**
     * List of mails,
     * @see Cws\MailBounceHandler\Models\Mail
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
