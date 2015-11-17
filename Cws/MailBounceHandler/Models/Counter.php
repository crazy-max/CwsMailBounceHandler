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

class Counter
{
    /**
     * Total messages in the mailbox/folder
     * @var int
     */
    private $total;
    
    /**
     * Fetched messages in the mailbox/folder
     * @var int
     */
    private $fetched;
    
    /**
     * Message processed
     * @var int
     */
    private $processed;
    
    /**
     * Messages unprocessed
     * @var int
     */
    private $unprocessed;
    
    /**
     * Messages unprocessed deleted
     * @var int
     */
    private $deleted;
    
    /**
     * Messages moved
     * @var int
     */
    private $moved;
    
    public function __construct()
    {
        $this->total = 0;
        $this->fetched = 0;
        $this->processed = 0;
        $this->unprocessed = 0;
        $this->deleted = 0;
        $this->moved = 0;
    }
    
    public function getTotal()
    {
        return $this->total;
    }

    public function setTotal($total)
    {
        $this->total = $total;
    }

    public function getFetched()
    {
        return $this->fetched;
    }

    public function setFetched($fetched)
    {
        $this->fetched = $fetched;
    }

    public function getProcessed()
    {
        return $this->processed;
    }

    public function incrProcessed()
    {
        $this->processed++;
    }
    
    public function setProcessed($processed)
    {
        $this->processed = $processed;
    }

    public function getUnprocessed()
    {
        return $this->unprocessed;
    }
    
    public function incrUnprocessed()
    {
        $this->unprocessed++;
    }

    public function setUnprocessed($unprocessed)
    {
        $this->unprocessed = $unprocessed;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }
    
    public function incrDeleted()
    {
        $this->deleted++;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getMoved()
    {
        return $this->moved;
    }
    
    public function incrMoved()
    {
        $this->moved++;
    }

    public function setMoved($moved)
    {
        $this->moved = $moved;
    }
}
