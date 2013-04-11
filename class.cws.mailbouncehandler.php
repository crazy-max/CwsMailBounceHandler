<?php

/**
 * CwsMailBounceHandler
 *
 * CwsMailBounceHandler is a PHP toolkit : (CwsMailBounceHandler and CwsMailBounceHandlerRules)
 * forked from PHPMailer-BMH (Bounce Mail Handler) v5.0.0rc1 at
 * http://phpmailer.codeworxtech.com by Andy Prevost to help webmasters
 * handle bounce-back mails in standard DSN (Delivery Status Notification, RFC-1894).
 * It checks your IMAP/POP3 inbox and delete all 'hard' bounced emails.
 * A result var is available to process custom post-actions.
 * 
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 * 
 * Please see the GNU General Public License at http://www.gnu.org/licenses/.
 *
 * @package CwsMailBounceHandler
 * @author Cr@zy
 * @copyright 2013, Cr@zy
 * @license GPL licensed
 * @version 1.0
 * @link https://github.com/crazy-max/CwsMailBounceHandler
 *
 */

define('CWSMBH_VERBOSE_QUIET',  0); // means no output at all
define('CWSMBH_VERBOSE_SIMPLE', 1); // means only output simple report
define('CWSMBH_VERBOSE_REPORT', 2); // means output a detail report
define('CWSMBH_VERBOSE_DEBUG',  3); // means output detail report as well as debug info.

define('CWSMBH_CERT_NOVALIDATE', 'novalidate-cert'); // do not validate certificates from TLS/SSL server
define('CWSMBH_CERT_VALIDATE',   'validate-cert');   // validate certificates from TLS/SSL server (default behavior)

require_once('class.cws.mailbouncehandler.rules.php');

class CwsMailBounceHandler extends CwsMailBounceHandlerRules
{
    /**
     * CwsMailBounceHandler version.
     * @var string
     */
    public $version = "1.0";
    
    /**
     * Mail host server.
     * default 'localhost'
     * @var string
     */
    public $host = 'localhost';
    
    /**
     * The username of mailbox.
     * @var string
     */
    public $username;
    
    /**
     * The password needed to access mailbox.
     * @var string
     */
    public $password;
    
    /**
     * Defines port number, other common choices are '110' (pop3), '143' (imap), '995' (tls/ssl)
     * default 143
     * @var integer
     */
    public $port = 143;
    
    /**
     * Defines service, choice includes 'pop3' and 'imap'.
     * default 'imap'
     * @var string
     */
    public $service = 'imap';
    
    /**
     * Defines service option, choices are 'none', 'notls', 'tls', 'ssl'.
     * default 'notls'
     * @var string
     */
    public $service_option = 'notls';
    
    /**
     * Control certificates validation if service_option is 'tls' or 'ssl'.
     * default CWSMBH_CERT_NOVALIDATE
     * @var string
     */
    public $cert = CWSMBH_CERT_NOVALIDATE;
    
    /**
     * Control the method to process the mail header.
     * If set true, uses the imap_fetchstructure function.
     * Otherwise, detect message type directly from headers, a bit faster than imap_fetchstructure function and take less resources. However - the difference is negligible.
     * default true
     * @var boolean
     */
    public $use_fetchstructure = true;
    
    /**
     * Mailbox type, other choices are (Tasks, Spam, Replies, etc.)
     * default 'INBOX'
     * @var string
     */
    public $boxname = 'INBOX';
    
    /**
     * Determines if soft bounces will be moved to another mailbox folder.
     * default false
     * @var boolean
     */
    public $move_soft = false;
    
    /**
     * Mailbox folder to move soft bounces to.
     * default 'INBOX.soft'
     * @var string
     */
    public $boxname_soft = 'INBOX.soft';
    
    /**
     * Determines if hard bounces will be moved to another mailbox folder.
     * NOTE: If true, this will disable delete and perform a move operation instead.
     * default false
     * @var boolean
     */
    public $move_hard = false;
    
    /**
     * Mailbox folder to move hard bounces to.
     * default 'INBOX.hard'
     * @var string
     */
    public $boxname_hard = 'INBOX.hard';

    /**
     * Maximum limit messages processed in one batch.
     * default 1000
     * @var int
     */
    public $max_messages = 1000;
    
    /**
     * The last error message.
     * @var string
     */
    public $error_msg;
    
    /**
     * Test mode, if true will not delete messages.
     * default false
     * @var boolean
     */
    public $test_mode = false;
    
    /**
     * Purge unknown messages. Be careful with this option.
     * default false
     * @var boolean
     */
    public $purge = false;
    
    /**
     * Control the debug output.
     * default CWSMBH_VERBOSE_SIMPLE
     * @var int
     */
    public $debug_verbose = CWSMBH_VERBOSE_SIMPLE;

    /**
     * If true, it will disable the delete function.
     * default false
     * @var boolean
     */
    public $disable_delete = false;
    
    /**
     * Result array of process
     * This var returns :
     *    array        'resume'        global result of the process.
     *           int        'total'        total messages in the mailbox.
     *           int        'fetched'      fetched messages from the mailbox.
     *           int        'processed'    messages processed.
     *           int        'unprocessed'  messages unprocessed.
     *           int        'deleted'      messages deleted.
     *           int        'moved'        messages moved.
     *    array        'msg'           list of msgs
     *           int        'msg_no'       message number.
     *           string     'bounce_type'  type of bounce (see defines in rules class)
     *           string     'email'        the email.
     *           string     'subject'      the email's subject.
     *           string     'xheader'      dns black list.
     *           string     'remove'       is removed.
     *           string     'rule_no'      rule number.
     *           string     'rule_cat'     rule category.
     *           string     'body'         body of the message.
     * @var array
     */
    public $result = array(
        'resume'    => array(
            'total'        => 0,
            'fetched'      => 0,
            'processed'    => 0,
            'unprocessed'  => 0,
            'deleted'      => 0,
            'moved'        => 0,
        ),
        'msgs'      => array(),
    );
    
    /**
     * The resource handler for the opened mailbox (POP3/IMAP/NNTP/etc.)
     * @var object
     */
    private $_handler = false;
    
    /**
     * Defines new line ending.
     */
    private $_newline = "<br />\n";
    
    /**
     * Subtype name.
     */
    private $_subtype_name = 'report';
    
    /**
     * Type of report parameter.
     */
    private $_report_type = 'report-type';
    
    /**
     * Delivery Status Notification name (DSN).
     */
    private $_dsn_name = 'delivery-status';
    
    /**
     * Output additional msg for debug
     * @param string $msg : if not given, output the last error msg
     * @param string $verbose_level : the output level of this message
     */
    private function output($msg=false, $verbose_level=CWSMBH_VERBOSE_SIMPLE, $newline=true)
    {
        if ($this->debug_verbose >= $verbose_level) {
            if (empty($msg)) {
                echo 'ERROR: ' . $this->error_msg;
            } else {
                echo $msg;
            }
            if ($newline) {
                echo $this->_newline;
            }
        }
    }
    
    /**
     * Open a remote mail box
     * @return boolean
     */
    public function openRemote()
    {
        $this->output('<h2>Init openRemote</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        
        // disable move operations if server is Gmail ... Gmail does not support mailbox creation
        if (stristr($this->host,'gmail')) {
            $this->move_soft = false;
            $this->move_hard = false;
        }
        
        // required options for imap_open connection.
        $opts = '/' . $this->service . '/' . $this->service_option;
        if ($this->service_option == 'tls' || $this->service_option == 'ssl') {
            $opts .= '/' . $this->cert;
        }
        
        set_time_limit(6000);
        
        $this->_handler = imap_open("{" . $this->host . ":" . $this->port . $opts . "}" . $this->boxname, $this->username, $this->password, !$this->test_mode ? CL_EXPUNGE : null);

        if (!$this->_handler) {
            $this->error_msg = '<strong>Cannot create ' . $this->service . ' connection</strong> to ' . $this->host . $this->_newline . 'Error MSG: ' . imap_last_error();
            $this->output();
            return false;
        } else {
            $this->output('<strong>Connected to:</strong> ' . $this->host . ":" . $this->port . $opts . ' on mailbox ' . $this->boxname . ' (' . $this->username . ')');
            return true;
        }
    }

    /**
     * Open a mail box in local file system
     * @param string $file_path : the local mailbox file path
     * @return boolean
     */
    public function openLocal($file_path)
    {
        $this->output('<h2>Init openLocal</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        
        set_time_limit(6000);
        
        $this->_handler = imap_open("$file_path", '', '', !$this->test_mode ? CL_EXPUNGE : null);
        
        if (!$this->_handler) {
            $this->error_msg = '<strong>Cannot open the mailbox file to ' . $file_path . '</strong>' . $this->_newline . 'Error MSG: ' . imap_last_error();
            $this->output();
            return false;
        } else {
            $this->output('<strong>Opened:</strong> ' . $file_path);
            return true;
        }
    }

    /**
     * Process the messages in a mailbox
     * @param string $max : maximum limit messages processed in one batch, if not given uses the property $max_messages.
     * @return boolean
     */
    public function processMailbox($max=false)
    {
        $this->output('<h2>Init processMailbox</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        
        if (!$this->_handler) {
            $this->error_msg = '<strong>Mailbox not opened</strong>';
            $this->output();
            exit();
        }
        
        if ($this->move_hard && $this->disable_delete === false) {
            $this->disable_delete = true;
        }
        
        if (!empty($max)) {
            $this->max_messages = $max;
        }
        
        // initialize counters
        $c_total = imap_num_msg($this->_handler);
        $c_fetched = $c_total;
        $c_processed = 0;
        $c_unprocessed = 0;
        $c_deleted = 0;
        $c_moved = 0;
        $this->output('<strong>Total:</strong> ' . $c_total . ' messages.');
        
        // process maximum number of messages
        if ($c_fetched > $this->max_messages) {
            $c_fetched = $this->max_messages;
            $this->output('Processing first <strong>' . $c_fetched . ' messages</strong>...');
        }
        
        $this->result['resume'] = array(
            'total'        => $c_total,
            'fetched'      => $c_fetched,
            'processed'    => $c_processed,
            'unprocessed'  => $c_unprocessed,
            'deleted'      => $c_deleted,
            'moved'        => $c_moved,
        );
        
        if ($this->test_mode) {
            $this->output('Running in <strong>test mode</strong>, not deleting messages from mailbox.');
        } else {
            if ($this->disable_delete) {
                if ($this->move_hard) {
                    $this->output('Running in <strong>move mode</strong>.');
                } else {
                    $this->output('Running in <strong>disable_delete mode</strong>, not deleting messages from mailbox.');
                }
            } else {
                $this->output('<strong>Processed messages will be deleted</strong> from mailbox.');
            }
        }
        
        // fetch the messages one at a time
        for ($x = 1; $x <= $c_fetched; $x++) {
            if ($this->use_fetchstructure) {
                $structure = imap_fetchstructure($this->_handler,$x);
                if ($structure->ifsubtype) {
                    $structure->subtype = strtolower($structure->subtype);
                }
                if ($structure->type == 1 && $structure->ifsubtype && $structure->subtype == $this->_subtype_name && $structure->ifparameters && $this->isParameter($structure->parameters)) {
                    $processed = $this->processBounce($x, 'DSN');
                } else {
                    // not standard DSN msg
                    $this->output('<strong>Msg #' .    $x . '</strong> is not a standard DSN message', CWSMBH_VERBOSE_REPORT);
                    if ($this->debug_verbose == CWSMBH_VERBOSE_DEBUG) {
                        if ($structure->ifdescription) {
                            $this->output('<strong>Content-Type:</strong> {' . $structure->description . '}', CWSMBH_VERBOSE_DEBUG);
                        } else {
                            $this->output('<strong>Content-Type:</strong> unsupported', CWSMBH_VERBOSE_DEBUG);
                        }
                    }
                    $processed = $this->processBounce($x, 'BODY');
                }
            } else {
                $header = imap_fetchheader($this->_handler, $x);
                // Could be multi-line, if the new line begins with SPACE or HTAB
                if (preg_match('#Content-Type:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)#is', $header, $match)) {
                    if (preg_match('#multipart/' . $this->_subtype_name . '#is', $match[1]) && preg_match('#' . $this->_report_type . '=["\']?' . $this->_dsn_name . '["\']?#is', $match[1])) {
                        $processed = $this->processBounce($x, 'DSN');
                    } else {
                        // not standard DSN msg
                        $this->output('<strong>Msg #' . $x . '</strong> is not a standard DSN message', CWSMBH_VERBOSE_REPORT);
                        if ($this->debug_verbose == CWSMBH_VERBOSE_DEBUG) {
                            $this->output('<strong>Content-Type:</strong> {' . $match[1] . '}', CWSMBH_VERBOSE_DEBUG);
                        }
                        $processed = $this->processBounce($x, 'BODY');
                    }
                } else {
                    // didn't get content-type header
                    $this->output('<strong>Msg #' . $x . '</strong> is not a well-formatted MIME mail, missing Content-Type', CWSMBH_VERBOSE_REPORT);
                    if ($this->debug_verbose == CWSMBH_VERBOSE_DEBUG) {
                        $this->output('<strong>Headers:</strong> ' . $this->_newline . $header . $this->_newline, CWSMBH_VERBOSE_DEBUG);
                    }
                    $processed = $this->processBounce($x, 'BODY');
                }
            }
            
            $deleteFlag[$x] = false;
            $moveFlag[$x] = false;
            if ($processed) {
                $c_processed++;
                if ($this->test_mode === false && $this->disable_delete === false) {
                    // delete the bounce if not in test mode and not in disable_delete mode
                    @imap_delete($this->_handler, $x);
                    $deleteFlag[$x] = true;
                    $c_deleted++;
                } elseif ($this->move_hard) {
                    // check if the move directory exists, if not create it
                    $this->isMailboxExists($this->boxname_hard);
                    @imap_mail_move($this->_handler, $x, $this->boxname_hard);
                    $moveFlag[$x] = true;
                    $c_moved++;
                } elseif ($this->move_soft) {
                    // check if the move directory exists, if not create it
                    $this->isMailboxExists($this->boxname_soft);
                    @imap_mail_move($this->_handler, $x, $this->boxname_soft);
                    $moveFlag[$x] = true;
                    $c_moved++;
                }
            } else {
                // not processed
                $c_unprocessed++;
                if (!$this->test_mode && !$this->disable_delete && $this->purge) {
                    // delete this bounce if not in test mode, not in disable_delete mode, and the flag purge is set
                    @imap_delete($this->_handler,$x);
                    $deleteFlag[$x] = true;
                    $c_deleted++;
                }
            }
            flush();
        }
        
        $this->output('<h2>End of process</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        $this->output('Closing mailbox, and purging messages');
        imap_close($this->_handler);
        
        $this->output('Read: ' . $c_fetched . ' messages');
        $this->output($c_processed . ' action taken' );
        $this->output($c_unprocessed . ' no action taken' );
        $this->output($c_deleted . ' messages deleted' );
        $this->output($c_moved . ' messages moved' );
        
        $this->result['resume']['fetched'] = $c_fetched;
        $this->result['resume']['processed'] = $c_processed;
        $this->result['resume']['unprocessed'] = $c_unprocessed;
        $this->result['resume']['deleted'] = $c_deleted;
        $this->result['resume']['moved'] = $c_moved;
        
        $this->output('<h2>$result</h2>', CWSMBH_VERBOSE_REPORT, false);
        if (!empty($this->result)) {
            $this->output('<h3>resume</h3>', CWSMBH_VERBOSE_REPORT, false);
            foreach ($this->result['resume'] as $key => $value) {
                $this->output('<strong>' . $key . '</strong> => ' . $value, CWSMBH_VERBOSE_REPORT);
            }
            $this->output('<h3>msgs</h3>', CWSMBH_VERBOSE_REPORT, false);
            if (!empty($this->result['msgs'])) {
                foreach ($this->result['msgs'] as $msg) {
                    foreach ($msg as $key => $value) {
                        $this->output('<strong>' . $key . '</strong> => ' . $value, CWSMBH_VERBOSE_REPORT);
                    }
                    $this->output('&nbsp;', CWSMBH_VERBOSE_REPORT);
                }
            } else {
                $this->output('empty', CWSMBH_VERBOSE_REPORT);
            }
        }
        
        return true;
    }
    
    /**
     * Function to process each individual message
     * @param int $pos : message number
     * @param string $type : DNS or BODY type
     * @return boolean
     */
    private function processBounce($pos, $type)
    {
        $header = imap_header($this->_handler,$pos);
        $subject = strip_tags($header->subject);
        $body = '';
        
        if ($type == 'DSN') {
            // first part of DSN (Delivery Status Notification), human-readable explanation
            $dsn_msg = imap_fetchbody($this->_handler, $pos, "1");
            $dsn_msg_structure = imap_bodystruct($this->_handler, $pos, "1");
            
            if ($dsn_msg_structure->encoding == 4) {
                $dsn_msg = quoted_printable_decode($dsn_msg);
            } elseif ($dsn_msg_structure->encoding == 3) {
                $dsn_msg = base64_decode($dsn_msg);
            }
            
            // second part of DSN (Delivery Status Notification), delivery-status
            $dsn_report = imap_fetchbody($this->_handler, $pos, "2");
            
            // process bounces by DSN rules
            $rulesResult = $this->processDSNRules($pos, $dsn_msg, $dsn_report, $this->debug_verbose == CWSMBH_VERBOSE_DEBUG);
        } elseif ($type == 'BODY') {
            $structure = imap_fetchstructure($this->_handler, $pos);
            switch ($structure->type) {
                case 0:
                    // Content-type = text
                    $body = imap_fetchbody($this->_handler, $pos, "1");
                    $rulesResult = $this->processBodyRules($pos, $body, $structure, $this->debug_verbose == CWSMBH_VERBOSE_DEBUG);
                    break;
                case 1:
                    // Content-type = multipart
                    $body = imap_fetchbody($this->_handler, $pos, "1");
                    
                    // Detect encoding and decode - only base64
                    if ($structure->parts[0]->encoding == 4) {
                        $body = quoted_printable_decode($body);
                    } elseif ($structure->parts[0]->encoding == 3) {
                        $body = base64_decode($body);
                    }
                    $rulesResult = $this->processBodyRules($pos, $body, $structure, $this->debug_verbose == CWSMBH_VERBOSE_DEBUG);
                    break;
                case 2:
                    // Content-type = message
                    $body = imap_body($this->_handler, $pos);
                    if ($structure->encoding == 4) {
                        $body = quoted_printable_decode($body);
                    } elseif ($structure->encoding == 3) {
                        $body = base64_decode($body);
                    }
                    $body = substr($body, 0, 1000);
                    $rulesResult = $this->processBodyRules($pos, $body, $structure, $this->debug_verbose == CWSMBH_VERBOSE_DEBUG);
                    break;
                default:
                    // Unsupport Content-type
                    $this->output('Msg #' . $pos . ' is unsupported Content-Type:' . $structure->type, CWSMBH_VERBOSE_REPORT);
                    return false;
            }
        } else {
            // internal error
            $this->error_msg = 'Internal Error: unknown type';
            $this->output();
            return false;
        }
        
        $email = $rulesResult['email'];
        $bounce_type = $rulesResult['bounce_type'];
        if ($this->move_hard && $rulesResult['remove'] == 1) {
            $remove = 'moved (hard)';
        } elseif ($this->move_soft && $rulesResult['remove'] == 1) {
            $remove = 'moved (soft)';
        } elseif ($this->disable_delete) {
            $remove = 0;
        } else {
            $remove = $rulesResult['remove'];
        }
        $rule_no = $rulesResult['rule_no'];
        $rule_cat = $rulesResult['rule_cat'];
        $xheader = false;
        
        if ($rule_no != '0000') {
            if ($this->test_mode) {
                $this->output('<strong>Match:</strong> ' . $rule_no . ' (' . $rule_cat . '); ' . $bounce_type . '; ' . $email);
            }
            $this->result['msgs'][] = array(
                'msg_no'        => $pos,
                'bounce_type'   => $bounce_type,
                'email'         => $email,
                'subject'       => $subject,
                'xheader'       => $xheader,
                'remove'        => $remove,
                'rule_no'       => $rule_no,
                'rule_cat'      => $rule_cat,
                'body'          => $body,
            );
            return true;
        }
    }
    
    /**
     * Function to determine if a particular value is found in a imap_fetchstructure key
     * @param array $parameters : imap_fetstructure parameters
     * @return boolean
     */
    private function isParameter($parameters)
    {
        foreach ($parameters as $object) {
            $object->attribute = strtolower($object->attribute);
            $object->value = strtolower($object->value);
            if ($object->attribute == $this->_report_type) {
                if ($object->value == $this->_dsn_name) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Function to check if a mailbox exists
     * - if not found, it will create it
     * @param string $mailbox : the mailbox name, must be in 'INBOX.checkmailbox' format
     * @param boolean $create : whether or not to create the checkmailbox if not found, defaults to true
     * @return boolean
     */
    private function isMailboxExists($mailbox, $create=true)
    {
        if (trim($mailbox) == '' || !strstr($mailbox,'INBOX.')) {
            // this is a critical error with either the mailbox name blank or an invalid mailbox name need to stop processing and exit at this point
            $this->error_msg = "Invalid mailbox name for move operation. Cannot continue." . $this->_newline . "TIP: the mailbox you want to move the message to must include 'INBOX.' at the start.";
            $this->output();
            exit();
        }
        
        // required options for imap_open connection.
        $opts = '/' . $this->service . '/' . $this->service_option;
        if ($this->service_option == 'tls' || $this->service_option == 'ssl') {
            $opts .= '/' . $this->cert;
        }
        
        $handler = imap_open('{' . $this->host . ':' . $this->port . $opts . '}', $this->username, $this->password, !$this->test_mode ? CL_EXPUNGE : null);
        $list = imap_getmailboxes($handler, '{' . $this->host . ":" . $this->port . $opts . '}', '*');
        
        $mailbox_found = false;
        if (is_array($list)) {
            foreach ($list as $key => $val) {
                // get the mailbox name only
                $nameArr = split('}', imap_utf7_decode($val->name));
                $nameRaw = $nameArr[count($nameArr) - 1];
                if ($mailbox == $nameRaw) {
                    $mailbox_found = true;
                }
            }
            if ($mailbox_found === false && $create) {
                @imap_createmailbox($handler, imap_utf7_encode('{' . $this->host . ':' . $this->port . $opts . '}' . $mailbox));
                imap_close($handler);
                return true;
            } else {
                imap_close($handler);
                return false;
            }
        } else {
            imap_close($handler);
            return false;
        }
    }
}

?>
