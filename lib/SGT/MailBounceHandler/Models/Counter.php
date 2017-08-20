<?php

/**
 * Counter.
 *
 * @author Cr@zy
 * @copyright 2013-2016, Cr@zy
 * @license GNU LESSER GENERAL PUBLIC LICENSE
 *
 * @link https://github.com/seracid/CwsMailBounceHandler
 */

namespace SGT\MailBounceHandler\Models;

class Counter
{
    /**
     * Total messages in the mailbox/folder.
     *
     * @var int
     */
    protected $total;

    /**
     * Fetched messages in the mailbox/folder.
     *
     * @var int
     */
    protected $fetched;

    /**
     * Message processed.
     *
     * @var int
     */
    protected $processed;

    /**
     * Messages unprocessed.
     *
     * @var int
     */
    protected $unprocessed;

    /**
     * Messages unprocessed deleted.
     *
     * @var int
     */
    protected $deleted;

    /**
     * Messages moved.
     *
     * @var int
     */
    protected $moved;

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
