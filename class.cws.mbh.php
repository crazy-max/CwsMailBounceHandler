<?php

/**
 * CwsMailBounceHandler
 *
 * CwsMailBounceHandler is a PHP class to help webmasters handle bounce-back,
 * feedback loop and ARF mails in standard DSN (Delivery Status Notification, RFC-1894).
 * It checks your IMAP/POP3 inbox or eml files and delete or move all 'hard' bounced emails.
 * If a bounce is malformed, it tries to extract some useful information to parse status.
 * A result array is available to process custom post-actions.
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
 * Related post: http://goo.gl/Wrq8J
 *
 * @package CwsMailBounceHandler
 * @author Cr@zy
 * @copyright 2013, Cr@zy
 * @license GPL licensed
 * @version 1.2
 * @link https://github.com/crazy-max/CwsMailBounceHandler
 *
 */

define('CWSMBH_OPEN_MODE_IMAP',          0); // if you open a mailbox via imap.
define('CWSMBH_OPEN_MODE_FILE',          1); // if you open a eml file or a folder containing eml files.

define('CWSMBH_VERBOSE_QUIET',           0); // means no output at all.
define('CWSMBH_VERBOSE_SIMPLE',          1); // means only output simple report.
define('CWSMBH_VERBOSE_REPORT',          2); // means output a detail report.
define('CWSMBH_VERBOSE_DEBUG',           3); // means output detail report as well as debug info.

define('CWSMBH_CERT_NOVALIDATE',         'novalidate-cert'); // do not validate certificates from TLS/SSL server
define('CWSMBH_CERT_VALIDATE',           'validate-cert');   // validate certificates from TLS/SSL server (default behavior)

define('CWSMBH_BOUNCE_AUTOREPLY',        'autoreply');
define('CWSMBH_BOUNCE_BLOCKED',          'blocked');
define('CWSMBH_BOUNCE_GENERIC',          'generic');
define('CWSMBH_BOUNCE_HARD',             'hard');
define('CWSMBH_BOUNCE_SOFT',             'soft');
define('CWSMBH_BOUNCE_TEMPORARY',        'temporary');

define('CWSMBH_CAT_ANTISPAM',            'antispam');
define('CWSMBH_CAT_AUTOREPLY',           'autoreply');
define('CWSMBH_CAT_CONCURRENT',          'concurrent');
define('CWSMBH_CAT_CONTENT_REJECT',      'content_reject');
define('CWSMBH_CAT_COMMAND_REJECT',      'command_reject');
define('CWSMBH_CAT_DEFER',               'defer');
define('CWSMBH_CAT_DELAYED',             'delayed');
define('CWSMBH_CAT_DNS_LOOP',            'dns_loop');
define('CWSMBH_CAT_DNS_UNKNOWN',         'dns_unknown');
define('CWSMBH_CAT_FULL',                'full');
define('CWSMBH_CAT_INACTIVE',            'inactive');
define('CWSMBH_CAT_INTERNAL_ERROR',      'internal_error');
define('CWSMBH_CAT_LATIN_ONLY',          'latin_only');
define('CWSMBH_CAT_OTHER',               'other');
define('CWSMBH_CAT_OVERSIZE',            'oversize');
define('CWSMBH_CAT_TIMEOUT',             'timeout');
define('CWSMBH_CAT_UNKNOWN',             'unknown');
define('CWSMBH_CAT_UNRECOGNIZED',        'unrecognized');
define('CWSMBH_CAT_USER_REJECT',         'user_reject');
define('CWSMBH_CAT_WARNING',             'warning');

define('CWSMBH_STATUS_FIRST_SUBCODE',    'first_subcode');
define('CWSMBH_STATUS_SECOND_SUBCODE',   'second_subcode');
define('CWSMBH_STATUS_THIRD_SUBCODE',    'third_subcode');

class CwsMailBounceHandler
{
    /**
     * CwsMailBounceHandler version.
     * @var string
     */
    public $version = "1.2";
    
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
     * Control the method to open e-mail(s).
     * default CWSMBH_OPEN_MODE_IMAP
     * @var int
     */
    public $open_mode = CWSMBH_OPEN_MODE_IMAP;
    
    /**
     * Mailbox type, other choices are (Tasks, Spam, Replies, etc.)
     * default 'INBOX'
     * @var string
     */
    public $boxname = 'INBOX';
    
    /**
     * Determines if soft bounces will be moved to another mailbox or folder.
     * default false
     * @var boolean
     */
    public $move_soft = false;
    
    /**
     * Mailbox or folder to move soft bounces to.
     * default 'INBOX.soft'
     * @var string
     */
    public $folder_soft = 'INBOX.soft';
    
    /**
     * Determines if hard bounces will be moved to another mailbox or folder.
     * NOTE: If true, this will disable delete and perform a move operation instead.
     * default false
     * @var boolean
     */
    public $move_hard = false;
    
    /**
     * Mailbox or folder to move hard bounces to.
     * default 'INBOX.hard'
     * @var string
     */
    public $folder_hard = 'INBOX.hard';

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
     * default CWSMBH_VERBOSE_QUIET
     * @var int
     */
    public $debug_verbose = CWSMBH_VERBOSE_QUIET;

    /**
     * If true, it will disable the delete function.
     * default false
     * @var boolean
     */
    public $disable_delete = false;
    
    /**
     * The resource handler for the opened mailbox (POP3/IMAP/NNTP/etc.)
     * @var object
     */
    private $_handler = false;
    
    /**
     * The eml files opened.
     * @var array
     */
    private $_files = array();
    
    /**
     * The folder path opened.
     * @var string
     */
    private $_folder = '';
    
    /**
     * The header of the message.
     * @var string
     */
    private $_header = array();
    
    /**
     * * The body of the message.
     * @var string
     */
    private $_body = array();
    
    /**
     * Defines new line ending.
     */
    private $_newline = "<br />\n";
    
    /**
     * Result array of the process
     * This var returns an array with the following values :
     *    array        'counter'       see $_counter_result.
     *    array        'msg'           see $_msg_result.
     * @var array
     */
    public $result = array(
        'counter'   => array(),
        'msgs'      => array(),
    );
    
    /**
     * Result array of the final counter
     * This var returns an array with the following values :
     *    int        'total'        total messages in the mailbox/folder.
     *    int        'fetched'      fetched messages from the mailbox/folder.
     *    int        'processed'    messages processed.
     *    int        'unprocessed'  messages unprocessed.
     *    int        'deleted'      messages deleted.
     *    int        'moved'        messages moved.
     * @var array
     */
    private $_counter_result = array(
        'total'        => 0,
        'fetched'      => 0,
        'processed'    => 0,
        'unprocessed'  => 0,
        'deleted'      => 0,
        'moved'        => 0,
    );
    
    /**
     * Result array of a msg process
     * This var returns an array with the following values :
     *   string     'token'        message number or filename.
     *   boolean    'processed'    was processed during bounce/fbl analyze.
     *   string     'subject'      message subject.
     *   array      'type'         type detected (bounce or fbl).
     *   array      'recipients'   see $_recipient_result.
     * @var array
     */
    private $_msg_result = array(
        'token'       => null,
        'processed'   => true,
        'subject'     => null,
        'type'        => null,
        'recipients'  => array(),
    );
    
    /**
     * Result array of recipients found
     * This var returns an array with the following values :
     *   string     'action'       the DSN action (only for DSN process).
     *   string     'status'       the status code.
     *   string     'email'        the recipient e-mail.
     *   string     'bounce_type'  type of bounce (see CWSMBH_BOUNCE_ defines)
     *   string     'bounce_cat'   bounce category.
     *   string     'remove'       is removed.
     * @var array
     */
    private $_recipient_result = array(
        'action'      => null,
        'status'      => null,
        'email'       => null,
        'bounce_type' => null,
        'bounce_cat'  => CWSMBH_CAT_UNRECOGNIZED,
        'remove'      => false,
    );
    
    /**
     * Result array of status
     * This var returns an array with the following values :
     *   string     'code'                             the status code.
     *   array      CWSMBH_STATUS_FIRST_SUBCODE        array containing title and description of the first subcode.
     *   array      CWSMBH_STATUS_SECOND_SUBCODE       array containing title and description of the second subcode.
     *   array      CWSMBH_STATUS_THIRD_SUBCODE        array containing title and description of the third subcode.
     * @var array
     */
    private $_status_result = array(
        'code'                        => null,
        CWSMBH_STATUS_FIRST_SUBCODE   => array(),
        CWSMBH_STATUS_SECOND_SUBCODE  => array(),
        CWSMBH_STATUS_THIRD_SUBCODE   => array(),
    );
    
    /**
     * Defines rules categories
     * @var array
     */
    private $_rules_cats = array
    (
        CWSMBH_CAT_ANTISPAM => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_BLOCKED,
        ),
        CWSMBH_CAT_AUTOREPLY => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_AUTOREPLY,
        ),
        CWSMBH_CAT_CONCURRENT => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_SOFT,
        ),
        CWSMBH_CAT_CONTENT_REJECT => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_SOFT,
        ),
        CWSMBH_CAT_COMMAND_REJECT => array
        (
            'remove'         => true,
            'bounce_type'    => CWSMBH_BOUNCE_HARD,
        ),
        CWSMBH_CAT_DEFER => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_SOFT,
        ),
        CWSMBH_CAT_DELAYED => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_TEMPORARY,
        ),
        CWSMBH_CAT_DNS_LOOP => array
        (
            'remove'         => true,
            'bounce_type'    => CWSMBH_BOUNCE_HARD,
        ),
        CWSMBH_CAT_DNS_UNKNOWN => array
        (
            'remove'         => true,
            'bounce_type'    => CWSMBH_BOUNCE_HARD,
        ),
        CWSMBH_CAT_FULL => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_SOFT,
        ),
        CWSMBH_CAT_INACTIVE => array
        (
            'remove'         => true,
            'bounce_type'    => CWSMBH_BOUNCE_HARD,
        ),
        CWSMBH_CAT_INTERNAL_ERROR => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_TEMPORARY,
        ),
        CWSMBH_CAT_LATIN_ONLY => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_SOFT,
        ),
        CWSMBH_CAT_OTHER => array
        (
            'remove'         => true,
            'bounce_type'    => CWSMBH_BOUNCE_GENERIC,
        ),
        CWSMBH_CAT_OVERSIZE => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_SOFT,
        ),
        CWSMBH_CAT_TIMEOUT => array
        (
        'remove'         => false,
        'bounce_type'    => CWSMBH_BOUNCE_SOFT,
        ),
        CWSMBH_CAT_UNKNOWN => array
        (
            'remove'         => true,
            'bounce_type'    => CWSMBH_BOUNCE_HARD,
        ),
        CWSMBH_CAT_UNRECOGNIZED => array
        (
            'remove'         => false,
            'bounce_type'    => null,
        ),
        CWSMBH_CAT_USER_REJECT => array
        (
            'remove'         => true,
            'bounce_type'    => CWSMBH_BOUNCE_HARD,
        ),
        CWSMBH_CAT_WARNING => array
        (
            'remove'         => false,
            'bounce_type'    => CWSMBH_BOUNCE_SOFT,
        ),
    );
    
    /**
     * Find status code from regexp
     * @var array
     */
    private $_status_code_resolver = array
    (
        // get from regexp
        '[45]\d\d[- ]\#?([45]\.\d\.\d)'                                 => 'x',
        'Diagnostic[- ][Cc]ode: smtp; ?\d\d\ ([45]\.\d\.\d)'            => 'x',
        'Status: ([45]\.\d\.\d)'                                        => 'x',
        // 4.2.0
        'not yet been delivered'                                        => '4.2.0',
        'message will be retried for'                                   => '4.2.0',
        // 4.2.2
        'benutzer hat zuviele mails auf dem server'                     => '4.2.2',
        'exceeded storage allocation'                                   => '4.2.2',
        'mailbox full'                                                  => '4.2.2',
        'mailbox is full'                                               => '4.2.2',
        'mailbox quota usage exceeded'                                  => '4.2.2',
        'mailbox size limit exceeded'                                   => '4.2.2',
        'mailfolder is full'                                            => '4.2.2',
        'not enough storage space'                                      => '4.2.2',
        'over ?quota'                                                   => '4.2.2',
        'quota exceeded'                                                => '4.2.2',
        'quota violation'                                               => '4.2.2',
        'user has exhausted allowed storage space'                      => '4.2.2',
        'user has too many messages on the server'                      => '4.2.2',
        'user mailbox exceeds allowed size'                             => '4.2.2',
        'user has Exceeded'                                             => '4.2.2',
        // 4.3.2
        'delivery attempts will continue to be made for'                => '4.3.2',
        'delivery temporarily suspended'                                => '4.3.2',
        'greylisted for 5 minutes'                                      => '4.3.2',
        'greylisting in action'                                         => '4.3.2',
        'server busy'                                                   => '4.3.2',
        'server too busy'                                               => '4.3.2',
        'system load is too high'                                       => '4.3.2',
        'temporarily deferred'                                          => '4.3.2',
        'temporarily unavailable'                                       => '4.3.2',
        'throttling'                                                    => '4.3.2',
        'too busy to accept mail'                                       => '4.3.2',
        'too many connections'                                          => '4.3.2',
        'too many sessions'                                             => '4.3.2',
        'too much load'                                                 => '4.3.2',
        'try again later'                                               => '4.3.2',
        'try later'                                                     => '4.3.2',
        // 4.4.7
        'retry timeout exceeded'                                        => '4.4.7',
        'queue too long'                                                => '4.4.7',
        // 5.1.1
        '554 delivery error:'                                           => '5.1.1',
        'account has been disabled'                                     => '5.1.1',
        'account is unavailable'                                        => '5.1.1',
        'account not found'                                             => '5.1.1',
        'address invalid'                                               => '5.1.1',
        'address is unknown'                                            => '5.1.1',
        'address unknown'                                               => '5.1.1',
        'addressee unknown'                                             => '5.1.1',
        'address_not_found'                                             => '5.1.1',
        'bad address'                                                   => '5.1.1',
        'bad destination mailbox address'                               => '5.1.1',
        'destin. Sconosciuto'                                           => '5.1.1',
        'destinatario errato'                                           => '5.1.1',
        'destinatario sconosciuto o mailbox disatttivata'               => '5.1.1',
        'does not exist'                                                => '5.1.1',
        'email Address was not found'                                   => '5.1.1',
        'excessive userid unknowns'                                     => '5.1.1',
        'Indirizzo inesistente'                                         => '5.1.1',
        'Invalid account'                                               => '5.1.1',
        'invalid address'                                               => '5.1.1',
        'invalid or unknown virtual user'                               => '5.1.1',
        'invalid mailbox'                                               => '5.1.1',
        'invalid recipient'                                             => '5.1.1',
        'mailbox not found'                                             => '5.1.1',
        'mailbox unavailable'                                           => '5.1.1',
        'nie istnieje'                                                  => '5.1.1',
        'nie ma takiego konta'                                          => '5.1.1',
        'no mail box available for this user'                           => '5.1.1',
        'no mailbox here'                                               => '5.1.1',
        'no one with that email address here'                           => '5.1.1',
        'no such address'                                               => '5.1.1',
        'no such email address'                                         => '5.1.1',
        'no such mail drop defined'                                     => '5.1.1',
        'no such mailbox'                                               => '5.1.1',
        'no such person at this address'                                => '5.1.1',
        'no such recipient'                                             => '5.1.1',
        'no such user'                                                  => '5.1.1',
        'not a known user'                                              => '5.1.1',
        'not a valid mailbox'                                           => '5.1.1',
        'not a valid user'                                              => '5.1.1',
        'not available'                                                 => '5.1.1',
        'not exists'                                                    => '5.1.1',
        'recipient address rejected'                                    => '5.1.1',
        'recipient not allowed'                                         => '5.1.1',
        'recipient not found'                                           => '5.1.1',
        'recipient rejected'                                            => '5.1.1',
        'recipient unknown'                                             => '5.1.1',
        'server doesn\'t handle mail for that user'                     => '5.1.1',
        'this account is disabled'                                      => '5.1.1',
        'this address no longer accepts mail'                           => '5.1.1',
        'this email address is not known to this system'                => '5.1.1',
        'unknown account'                                               => '5.1.1',
        'unknown address or alias'                                      => '5.1.1',
        'unknown email address'                                         => '5.1.1',
        'unknown local part'                                            => '5.1.1',
        'unknown or illegal alias'                                      => '5.1.1',
        'unknown or illegal user'                                       => '5.1.1',
        'unknown recipient'                                             => '5.1.1',
        'unknown user'                                                  => '5.1.1',
        'user disabled'                                                 => '5.1.1',
        'user doesn\'t exist in this server'                            => '5.1.1',
        'user invalid'                                                  => '5.1.1',
        'user is suspended'                                             => '5.1.1',
        'user is unknown'                                               => '5.1.1',
        'user not found'                                                => '5.1.1',
        'user not known'                                                => '5.1.1',
        'user unknown'                                                  => '5.1.1',
        'valid RCPT command must precede data'                          => '5.1.1',
        'was not found in ldap server'                                  => '5.1.1',
        'we are sorry but the address is invalid'                       => '5.1.1',
        'unable to find alias user'                                     => '5.1.1',
        // 5.1.2
        'domain isn\'t in my list of allowed rcpthosts'                 => '5.1.2',
        'esta casilla ha expirado por falta de uso'                     => '5.1.2',
        'host ?name is unknown'                                         => '5.1.2',
        'no relaying allowed'                                           => '5.1.2',
        'no such domain'                                                => '5.1.2',
        'not our customer'                                              => '5.1.2',
        'relay not permitted'                                           => '5.1.2',
        'relay access denied'                                           => '5.1.2',
        'relaying denied'                                               => '5.1.2',
        'relaying not allowed'                                          => '5.1.2',
        'this system is not configured to relay mail'                   => '5.1.2',
        'unable to relay'                                               => '5.1.2',
        'unrouteable mail domain'                                       => '5.1.2',
        'we do not relay'                                               => '5.1.2',
        // 5.1.6
        'old address no longer valid'                                   => '5.1.6',
        'recipient no longer on server'                                 => '5.1.6',
        // 5.1.8
        'dender address rejected'                                       => '5.1.8',
        // 5.2.0
        'delivery failed'                                               => '5.2.0',
        'exceeded the rate limit'                                       => '5.2.0',
        'local Policy Violation'                                        => '5.2.0',
        'mailbox currently suspended'                                   => '5.2.0',
        'mailbox unavailable'                                           => '5.2.0',
        'mail can not be delivered'                                     => '5.2.0',
        'mail couldn\'t be delivered'                                   => '5.2.0',
        'the account or domain may not exist'                           => '5.2.0',
        // 5.2.1
        'account disabled'                                              => '5.2.1',
        'account has been disabled'                                     => '5.2.1',
        'account inactive'                                              => '5.2.1',
        'inactive account'                                              => '5.2.1',
        'adressat unbekannt oder mailbox deaktiviert'                   => '5.2.1',
        'destinataire inconnu ou boite aux lettres desactivee'          => '5.2.1',
        'mail is not currently being accepted for this mailbox'         => '5.2.1',
        'el usuario esta en estado: inactivo'                           => '5.2.1',
        'email account that you tried to reach is disabled'             => '5.2.1',
        'inactive user'                                                 => '5.2.1',
        'user is inactive'                                              => '5.2.1',
        'mailbox disabled for this recipient'                           => '5.2.1',
        'mailbox has been blocked due to inactivity'                    => '5.2.1',
        'mailbox is currently unavailable'                              => '5.2.1',
        'mailbox is disabled'                                           => '5.2.1',
        'mailbox is inactive'                                           => '5.2.1',
        'mailbox locked or suspended'                                   => '5.2.1',
        'mailbox temporarily disabled'                                  => '5.2.1',
        'podane konto jest zablokowane administracyjnie lub nieaktywne' => '5.2.1',
        'questo indirizzo e\' bloccato per inutilizzo'                  => '5.2.1',
        'recipient mailbox was disabled'                                => '5.2.1',
        'domain name not found'                                         => '5.2.1',
        // 5.4.4
        'couldn\'t find any host named'                                 => '5.4.4',
        'couldn\'t find any host by that name'                          => '5.4.4',
        'perm_failure: dns error'                                       => '5.4.4',
        'temporary lookup failure'                                      => '5.4.4',
        'unrouteable address'                                           => '5.4.4',
        'can\'t connect to'                                             => '5.4.4',
        // 5.4.6
        'too many hops'                                                 => '5.4.6',
        // 5.5.0
        'content reject'                                                => '5.5.0',
        'requested action aborted'                                      => '5.5.0',
        // 5.5.2
        'mime/reject'                                                   => '5.5.2',
        // 5.5.3
        'mail data refused'                                             => '5.5.3',
        // 5.5.4
        'mime error'                                                    => '5.5.4',
        // 5.6.2
        'rejecting password protected file attachment'                  => '5.6.2',
        // 5.7.1
        '550 OU-00'                                                     => '5.7.1',
        '550 SC-00'                                                     => '5.7.1',
        '550 DY-00'                                                     => '5.7.1',
        '554 denied'                                                    => '5.7.1',
        'you have been blocked by the recipient'                        => '5.7.1',
        'requires that you verify'                                      => '5.7.1',
        'access denied'                                                 => '5.7.1',
        'administrative prohibition - unable to validate recipient'     => '5.7.1',
        'blacklisted'                                                   => '5.7.1',
        'blocke?d? for spam'                                            => '5.7.1',
        'conection refused'                                             => '5.7.1',
        'connection refused due to abuse'                               => '5.7.1',
        'dial-up or dynamic-ip denied'                                  => '5.7.1',
        'domain has received too many bounces'                          => '5.7.1',
        'failed several antispam checks'                                => '5.7.1',
        'found in a dns blacklist'                                      => '5.7.1',
        'ips blocked'                                                   => '5.7.1',
        'is blocked by'                                                 => '5.7.1',
        'mail Refused'                                                  => '5.7.1',
        'message does not pass domainkeys'                              => '5.7.1',
        'message looks like spam'                                       => '5.7.1',
        'message refused by'                                            => '5.7.1',
        'not allowed access from your location'                         => '5.7.1',
        'permanently deferred'                                          => '5.7.1',
        'rejected by policy'                                            => '5.7.1',
        'rejected by windows live hotmail for policy reasons'           => '5.7.1',
        'rejected for policy reasons'                                   => '5.7.1',
        'rejecting banned content'                                      => '5.7.1',
        'sorry, looks like spam'                                        => '5.7.1',
        'spam message discarded'                                        => '5.7.1',
        'too many spams from your ip'                                   => '5.7.1',
        'transaction failed'                                            => '5.7.1',
        'transaction rejected'                                          => '5.7.1',
        'wiadomosc zostala odrzucona przez system antyspamowy'          => '5.7.1',
        'your message was declared spam'                                => '5.7.1' 
    );
    
    /**
     * Find rule cat from status code
     * @var array
     */
    private $_rule_cat_resolver = array
    (
        '4.2.0'    => CWSMBH_CAT_DEFER,
        '4.2.2'    => CWSMBH_CAT_FULL,
        '4.3.2'    => CWSMBH_CAT_DEFER,
        '4.4.7'    => CWSMBH_CAT_TIMEOUT,
        '4.5.1'    => CWSMBH_CAT_COMMAND_REJECT,
        '5.0.0'    => CWSMBH_CAT_UNKNOWN,
        '5.1.1'    => CWSMBH_CAT_UNKNOWN,
        '5.1.2'    => CWSMBH_CAT_UNKNOWN,
        '5.1.3'    => CWSMBH_CAT_UNKNOWN,
        '5.1.4'    => CWSMBH_CAT_UNKNOWN,
        '5.1.6'    => CWSMBH_CAT_UNKNOWN,
        '5.1.8'    => CWSMBH_CAT_ANTISPAM,
        '5.2.0'    => CWSMBH_CAT_FULL,
        '5.2.1'    => CWSMBH_CAT_USER_REJECT,
        '5.2.2'    => CWSMBH_CAT_FULL,
        '5.2.3'    => CWSMBH_CAT_UNKNOWN,
        '5.4.4'    => CWSMBH_CAT_UNKNOWN,
        '5.4.6'    => CWSMBH_CAT_ANTISPAM,
        '5.5.0'    => CWSMBH_CAT_CONTENT_REJECT,
        '5.5.2'    => CWSMBH_CAT_CONTENT_REJECT,
        '5.5.3'    => CWSMBH_CAT_CONTENT_REJECT,
        '5.5.4'    => CWSMBH_CAT_CONTENT_REJECT,
        '5.6.2'    => CWSMBH_CAT_CONTENT_REJECT,
        '5.7.1'    => CWSMBH_CAT_USER_REJECT,
    );
    
    /**
     * Output additional msg for debug
     * @param string $msg : if not given, output the last error msg
     * @param int $verbose_level : the output level of this message
     */
    private function output($msg=false, $verbose_level=CWSMBH_VERBOSE_SIMPLE, $newline=true, $code=false)
    {
        if ($this->debug_verbose >= $verbose_level) {
            if (empty($msg)) {
                echo 'ERROR: ' . $this->error_msg;
            } else {
                if ($code) {
                    echo '<textarea style="width:100%;height:300px;">';
                    print_r($msg);
                    echo '</textarea>';
                } else {
                    echo $msg;
                }
            }
            if ($newline) {
                echo $this->_newline;
            }
        }
    }
    
    /**
     * Open a folder containing eml files on your system
     * @param string $eml_folder_path : the eml folder path
     * @return boolean
     */
    public function openFolder($eml_folder_path)
    {
        $this->output('<h2>Init openFolder</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        
        if (!$this->endWith($eml_folder_path, '/')) {
            $eml_folder_path .= '/';
        }
        $this->_folder = $eml_folder_path;
        
        set_time_limit(30000);
        
        $handle = @opendir($this->_folder);
        if (!$handle) {
            $this->error_msg = '<strong>Cannot open the eml folder ' . $this->_folder . '</strong>.';
            $this->output();
            return false;
        }
        
        $nb_files = 0;
        while ($file = readdir($handle)) {
            if ($file == '.' || $file == '..' || !$this->endWith($file, '.eml')) {
                continue;
            }
            $eml_path = $this->_folder . '/' . $file;
            $content = @file_get_contents($eml_path, false, stream_context_create(array('http' => array('method' => 'GET'))));
            if (!empty($content)) {
                $this->_files[] = array(
                    'name'    => $file,
                    'content' => $content,
                );
            }
            $nb_files++;
        }
        closedir($handle);
        
        if (empty($this->_files)) {
            $this->error_msg = '<strong>no eml file found in ' . $this->_folder . '</strong>.';
            $this->output();
            return false;
        } else {
            $this->output('<strong>Opened:</strong> ' . count($this->_files) . ' / ' . $nb_files . ' files.');
            return true;
        }
    }
    
    /**
     * Open an eml file on your system
     * @param string $eml_path : the eml file path
     * @return boolean
     */
    public function openFile($eml_path)
    {
        $this->output('<h2>Init openFile</h2>', CWSMBH_VERBOSE_SIMPLE, false);
    
        set_time_limit(6000);
        
        $content = @file_get_contents($eml_path, false, stream_context_create(array('http' => array('method' => 'GET'))));
        if (!empty($content)) {
            $this->_files[] = array(
                'name'    => $file,
                'content' => $content,
            );
        }
        
        if (empty($this->_files)) {
            $this->error_msg = '<strong>Cannot open the eml file ' . $eml_path . '</strong>.';
            $this->output();
            return false;
        } else {
            $this->output('<strong>Opened:</strong> ' . $eml_path . '.');
            return true;
        }
    }
    
    /**
     * Open a mail box in local file system
     * @param string $file_path : the local mailbox file path
     * @return boolean
     */
    public function openImapLocal($file_path)
    {
        $this->output('<h2>Init openImapLocal</h2>', CWSMBH_VERBOSE_SIMPLE, false);
    
        set_time_limit(6000);
    
        $this->_handler = imap_open($file_path, '', '', !$this->test_mode ? CL_EXPUNGE : null);
    
        if (!$this->_handler) {
            $this->error_msg = '<strong>Cannot open the mailbox file to ' . $file_path . '</strong>' . $this->_newline . 'Error MSG: ' . imap_last_error();
            $this->output();
            return false;
        } else {
            $this->output('<strong>Opened:</strong> ' . $file_path . '.');
            return true;
        }
    }
    
    /**
     * Open a remote mail box
     * @return boolean
     */
    public function openImapRemote()
    {
        $this->output('<h2>Init openImapRemote</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        
        // disable move operations if server is Gmail... Gmail does not support mailbox creation
        if (stristr($this->host, 'gmail')) {
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
     * Process the messages in a mailbox or a file/folder
     * @param int $max : maximum limit messages processed in one batch, if not given uses the property $max_messages.
     * @return boolean
     */
    public function processMails($max=0)
    {
        $this->output('<h2>Init processMails</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        
        if ($this->isImapOpenMode()) {
            if (!$this->_handler) {
                $this->error_msg = '<strong>Mailbox not opened</strong>';
                $this->output();
                exit();
            }
        } else {
            if (empty($this->_files)) {
                $this->error_msg = '<strong>File(s) not opened</strong>';
                $this->output();
                exit();
            }
        }
        
        if ($this->move_hard && $this->disable_delete === false) {
            $this->disable_delete = true;
        }
        
        if (!empty($max)) {
            $this->max_messages = $max;
        }
        
        // initialize counter
        $this->result['counter'] = $this->_counter_result;
        $this->result['counter']['total'] = $this->isImapOpenMode() ? imap_num_msg($this->_handler) : count($this->_files);
        $this->result['counter']['fetched'] = $this->result['counter']['total'];
        $this->output('<strong>Total:</strong> ' . $this->result['counter']['total'] . ' messages.');
        
        // process maximum number of messages
        if ($this->result['counter']['fetched'] > $this->max_messages) {
            $this->result['counter']['fetched'] = $this->max_messages;
            $this->output('Processing first <strong>' . $this->result['counter']['fetched'] . ' messages</strong>...');
        }
        
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
        
        if ($this->isImapOpenMode()) {
            for ($msg_no = 1; $msg_no <= $this->result['counter']['fetched']; $msg_no++) {
                $this->output('<h3>Msg #' . $msg_no . '</h3>', CWSMBH_VERBOSE_REPORT, false);
                
                $header = @imap_fetchheader($this->_handler, $msg_no);
                $body = @imap_body($this->_handler, $msg_no);
                
                $this->result['msgs'][] = $this->processParsing($msg_no, $header . '\r\n\r\n' . $body);
            }
        } else {
            foreach ($this->_files as $file) {
                $this->output('<h3>Msg #' . $file['name'] . '</h3>', CWSMBH_VERBOSE_REPORT, false);
                $this->result['msgs'][] = $this->processParsing($file['name'], $file['content']);
            }
        }
        
        foreach ($this->result['msgs'] as $msg) {
            if ($msg['processed']) {
                $this->result['counter']['processed']++;
                if (!$this->test_mode && !$this->disable_delete) {
                    $this->processDelete($msg['token']);
                    $this->result['counter']['deleted']++;
                } elseif ($this->move_hard) {
                    $this->processMove($msg['token'], 'hard');
                    $this->result['counter']['moved']++;
                } elseif ($this->move_soft) {
                    $this->processMove($msg['token'], 'soft');
                    $this->result['counter']['moved']++;
                }
            } else {
                $this->result['counter']['unprocessed']++;
                if (!$this->test_mode && !$this->disable_delete && $this->purge) {
                    $this->processDelete($msg['token']);
                    $this->result['counter']['deleted']++;
                }
            }
        }
        
        $this->output('<h2>End of process</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        if ($this->isImapOpenMode()) {
            $this->output('Closing mailbox, and purging messages');
            @imap_close($this->_handler);
        }
        
        $this->output($this->result['counter']['fetched'] . ' messages read');
        $this->output($this->result['counter']['processed'] . ' action taken');
        $this->output($this->result['counter']['unprocessed'] . ' no action taken');
        $this->output($this->result['counter']['deleted'] . ' messages deleted');
        $this->output($this->result['counter']['moved'] . ' messages moved');
        
        $this->output($this->_newline . '<strong>Full result:</strong>', CWSMBH_VERBOSE_REPORT);
        $this->output($this->result, CWSMBH_VERBOSE_REPORT, false, true);
        
        return true;
    }
    
    /**
     * Function to process parsing of each individual message
     * @param string $token : message number or filename.
     * @param string $content : message content.
     * @return array
     */
    private function processParsing($token, $content)
    {
        $result = $this->_msg_result;
        $result['token'] = $token;
        
        // format content
        $content = $this->formatContent($content);
        
        // split head and body
        if (preg_match('#\r\n\r\n#is', $content)) {
            list($header, $body) = preg_split('#\r\n\r\n#', $content, 2);
        } else {
            list($header, $body) = preg_split('#\n\n#', $content, 2);
        }
        
        $this->output('<strong>Header:</strong>', CWSMBH_VERBOSE_DEBUG);
        $this->output($header, CWSMBH_VERBOSE_DEBUG, false, true);
        $this->output('<strong>Body:</strong>', CWSMBH_VERBOSE_DEBUG);
        $this->output($body, CWSMBH_VERBOSE_DEBUG, false, true);
        $this->output('&nbsp;', CWSMBH_VERBOSE_DEBUG);
        
        // parse header
        $header = $this->parseHeader($header);
        
        // parse body sections
        $body_sections = $this->parseBodySections($header, $body);
        
        // check bounce and fbl
        $is_bounce = $this->isBounce($header);
        $is_fbl = $this->isFbl($header, $body_sections);
        
        if ($is_bounce) {
            $result['type'] = 'bounce';
        } elseif ($is_fbl) {
            $result['type'] = 'fbl';
        }
        
        // begin process
        $result['recipients'] = array();
        if ($is_fbl) {
            $this->output('<strong>Feedback loop</strong> detected', CWSMBH_VERBOSE_DEBUG);
            $result['subject'] = trim(str_ireplace('Fw:', '', $header['Subject']));
            
            if ($this->isHotmailFbl($body_sections)) {
                $this->output('This message is an <strong>Hotmail fbl</strong>', CWSMBH_VERBOSE_DEBUG);
                $body_sections['ar_machine']['Content-disposition'] = 'inline';
                $body_sections['ar_machine']['Content-type'] = 'message/feedback-report';
                $body_sections['ar_machine']['Feedback-type'] = 'abuse';
                $body_sections['ar_machine']['User-agent'] = 'Hotmail FBL';
                if (!$this->isEmpty($body_sections['ar_first'], 'Date')) {
                    $body_sections['ar_machine']['Received-date'] = $body_sections['ar_first']['Date'];
                }
                if (!$this->isEmpty($body_sections['ar_first'], 'X-HmXmrOriginalRecipient')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $body_sections['ar_first']['X-HmXmrOriginalRecipient'];
                }
                if (!$this->isEmpty($body_sections['ar_first'], 'X-sid-pra')) {
                    $body_sections['ar_machine']['Original-mail-from'] = $body_sections['ar_first']['X-sid-pra'];
                }
            } else {
                if (!$this->isEmpty($body_sections, 'machine')) {
                    $body_sections['ar_machine'] = $this->parseLines($body_sections['machine']);
                }
                if (!$this->isEmpty($body_sections, 'returned')) {
                    $body_sections['ar_returned'] = $this->parseLines($body_sections['returned']);
                }
                if ($this->isEmpty($body_sections['ar_machine'], 'Original-mail-from') && !$this->isEmpty($body_sections['ar_returned'], 'From')) {
                    $body_sections['ar_machine']['Original-mail-from'] = $body_sections['ar_returned']['From'];
                }
                if ($this->isEmpty($body_sections['ar_machine'], 'Original-rcpt-to') && !$this->isEmpty($body_sections['ar_machine'], 'Removal-recipient')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $body_sections['ar_machine']['Removal-recipient'];
                } elseif (!$this->isEmpty($body_sections['ar_returned'], 'To')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $body_sections['ar_returned']['To'];
                }
                // try to get the actual intended recipient if possible
                if (preg_match('#Undisclosed|redacted#i', $body_sections['ar_machine']['Original-mail-from']) && !$this->isEmpty($body_sections['ar_machine'], 'Removal-recipient')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $body_sections['ar_machine']['Removal-recipient'];
                }
                if ($this->isEmpty($body_sections['ar_machine'], 'Received-date') && !$this->isEmpty($body_sections['ar_machine'], 'Arrival-date')) {
                    $body_sections['ar_machine']['Received-date'] = $body_sections['ar_machine']['Arrival-date'];
                }
                if (!$this->isEmpty($body_sections['ar_machine'], 'Original-mail-from')) {
                    $body_sections['ar_machine']['Original-mail-from'] = $this->extractEmail($body_sections['ar_machine']['Original-mail-from']);
                }
                if (!$this->isEmpty($body_sections['ar_machine'], 'Original-rcpt-to')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $this->extractEmail($body_sections['ar_machine']['Original-rcpt-to']);
                }
            }
            
            $recipient = $this->_recipient_result;
            $recipient['email'] = $body_sections['ar_machine']['Original-rcpt-to'];
            $recipient['status'] = '5.7.1';
            $recipient['action'] = 'failed';
            $result['recipients'][] = $recipient;
        } elseif (!$this->isEmpty($header, 'Subject') && preg_match('#auto.{0,20}reply|vacation|(out|away|on holiday).*office#i', $header['Subject'])) {
            $this->output('<strong>Autoreply</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $recipient = $this->_recipient_result;
            $recipient['bounce_cat'] = CWSMBH_CAT_AUTOREPLY;
            $result['recipients'][] = $recipient;
        } elseif ($this->isRfc1892Report($header)) {
            $this->output('<strong>RFC 1892 report</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $ar_body_machine = $this->parseBodySectionMachine($body_sections['machine']);
            if (!$this->isEmpty($ar_body_machine['per_recipient'])) {
                foreach ($ar_body_machine['per_recipient'] as $ar_recipient) {
                    $recipient = $this->_recipient_result;
                    $recipient['email'] = $this->findEmail($ar_recipient);
                    $recipient['status'] = isset($ar_recipient['Status']) ? $ar_recipient['Status'] : null;
                    $recipient['action'] = isset($ar_recipient['Action']) ? $ar_recipient['Action'] : null;
                    $result['recipients'][] = $recipient;
                }
            }
        } elseif (!$this->isEmpty($header, 'X-failed-recipients')) {
            $this->output('<strong>X-failed-recipients</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $ar_emails = explode(",", $header['X-failed-recipients']);
            foreach($ar_emails as $email) {
                $recipient = $this->_recipient_result;
                $recipient['email'] = trim($email);
                $result['recipients'][] = $recipient;
            }
        } elseif(isset($header['Content-type']) && !$this->isEmpty($header['Content-type'], 'boundary') && $this->isBounce($header)) {
            $this->output('<strong>First body part</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $ar_emails = $this->findEmails($body_sections['first']);
            foreach($ar_emails as $email) {
                $recipient = $this->_recipient_result;
                $recipient['email'] = trim($email);
                $result['recipients'][] = $recipient;
            }
        } elseif($this->isBounce($header)) {
            $this->output('<strong>Other bounces</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $ar_emails = $this->findEmails($body);
            foreach($ar_emails as $email) {
                $recipient = $this->_recipient_result;
                $recipient['email'] = trim($email);
                $result['recipients'][] = $recipient;
            }
        } else {
            $result['processed'] = false;
        }
        
        if (empty($result['subject']) && isset($header['Subject'])) {
            $result['subject'] = $header['Subject'];
        }
        if (!empty($result['recipients'])) {
            $tmp_recipient = $result['recipients'];
            $result['recipients'] = array();
            foreach($tmp_recipient as $recipient) {
                if (empty($recipient['status'])) {
                    $recipient['status'] = $this->findStatusCodeByRecipient($body);
                } else {
                    $recipient['status'] = $this->formatStatusCode($recipient['status']);
                }
                if (empty($recipient['action'])) {
                    $recipient['action'] = $this->findActionByStatusCode($recipient['status']);
                }
                if ($recipient['bounce_cat'] == CWSMBH_CAT_UNRECOGNIZED && !empty($recipient['status'])) {
                    foreach ($this->_rule_cat_resolver as $key => $value) {
                        if ($key == $recipient['status']) {
                            $recipient['bounce_cat'] = $value;
                        }
                        $recipient['bounce_type'] = $this->_rules_cats[$recipient['bounce_cat']]['bounce_type'];
                        $recipient['remove'] = $this->_rules_cats[$recipient['bounce_cat']]['remove'];
                    }
                }
                $result['recipients'][] = $recipient;
            }
        }
        
        $this->output('<strong>Result:</strong>', CWSMBH_VERBOSE_REPORT);
        $this->output($result, CWSMBH_VERBOSE_REPORT, false, true);
        
        return $result;
    }
    
    /**
     * Function to delete a message
     * @param string $token : message number or filename.
     * @return boolean
     */
    private function processDelete($token)
    {
        if ($this->isImapOpenMode()) {
            $this->output('Process <strong>delete</strong> message <strong>' . $token . '</strong>', CWSMBH_VERBOSE_DEBUG);
            return @imap_delete($this->_handler, $token);
        } else {
            $this->output('Process <strong>delete</strong> message <strong>' . $token . '</strong>', CWSMBH_VERBOSE_DEBUG);
            return @unlink($this->_folder . $token);
        }
    }
    
    /**
     * Function to move a message
     * @param string $token : message number or filename.
     * @param string $type : 'soft' or 'hard' bounce type.
     * @return boolean
     */
    private function processMove($token, $type)
    {
        if ($this->isImapOpenMode()) {
            if ($type == 'soft' && !empty($this->folder_soft)) {
                $this->output('Process <strong>move soft</strong> in ' . $this->folder_soft, CWSMBH_VERBOSE_DEBUG);
                $this->isMailboxExists($this->folder_soft);
                return @imap_mail_move($this->_handler, $token, $this->folder_soft);
            } elseif ($type == 'hard' && !empty($this->folder_hard)) {
                $this->output('Process <strong>move hard</strong> in ' . $this->folder_hard, CWSMBH_VERBOSE_DEBUG);
                $this->isMailboxExists($this->folder_hard);
                return @imap_mail_move($this->_handler, $token, $this->folder_hard);
            } else {
                $this->error_msg = 'The folder ' . $type . ' var is empty.';
                $this->output();
                return false;
            }
        } else {
            if ($type == 'soft' && !empty($this->folder_soft)) {
                if (!$this->endWith($this->folder_soft, '/')) {
                    $this->folder_soft .= '/';
                }
                if (!is_dir($this->folder_soft)) {
                    mkdir($this->folder_soft);
                }
                $this->output('Process <strong>move soft</strong> in ' . $this->folder_soft, CWSMBH_VERBOSE_DEBUG);
                return @rename($this->_folder . $token, $this->folder_soft . $token);
            } elseif ($type == 'hard' && !empty($this->folder_hard)) {
                if (!$this->endWith($this->folder_hard, '/')) {
                    $this->folder_hard .= '/';
                }
                if (!is_dir($this->folder_hard)) {
                    mkdir($this->folder_hard);
                }
                $this->output('Process <strong>move hard</strong> in ' . $this->folder_hard, CWSMBH_VERBOSE_DEBUG);
                return @rename($this->_folder . $token, $this->folder_hard . $token);
            } else {
                $this->error_msg = 'The folder ' . $type . ' var is empty.';
                $this->output();
                return false;
            }
        }
    }
    
    /**
     * Function to determine the current open mode
     * @return boolean
     */
    private function isImapOpenMode()
    {
        return $this->open_mode == CWSMBH_OPEN_MODE_IMAP;
    }
    
    /**
     * Function to check if a mailbox exists. If not found, it will create it.
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
    
    /**
     * Function to parse the header with some custom fields.
     * @param array $ar_header : the array or plain text headers
     * @return array
     */
    private function parseHeader($ar_header)
    {
        if (!is_array($ar_header)) {
            $ar_header = explode("\r\n", $ar_header);
        }
        $ar_header = $this->parseLines($ar_header);
    
        if (isset($ar_header['Received'])) {
            $arrRec = explode("|", $ar_header['Received']);
            $ar_header['Received']= $arrRec;
        }
    
        if (isset($ar_header['Content-type'])) {
            $ar_mr = explode(";", $ar_header['Content-type']);
            $ar_header['Content-type'] = '';
            $ar_header['Content-type']['type'] = strtolower($ar_mr[0]);
            foreach ($ar_mr as $mr) {
                if (preg_match('#([^=.]*?)=(.*)#i', $mr, $matches)) {
                    $ar_header['Content-type'][strtolower(trim($matches[1]))] = str_replace('"', '', $matches[2]);
                }
            }
        }
        
        foreach ($ar_header as $key => $value) {
            if (strtolower($key) == 'x-hmxmroriginalrecipient') {
                unset($ar_header[$key]);
                $ar_header['X-HmXmrOriginalRecipient'] = $value;
            }
        }
    
        return $ar_header;
    }
    
    /**
     * Function to parse body by sections.
     * @param array $ar_header : the array headers
     * @param string $body : the body content
     * @return array
     */
    private function parseBodySections($ar_header, $body)
    {
        $sections = array();
        
        if (is_array($ar_header) && isset($ar_header['Content-type']) && isset($ar_header['Content-type']['boundary'])) {
            $ar_boundary = explode($ar_header['Content-type']['boundary'], $body);
            $sections['first'] = isset($ar_boundary[1]) ? $ar_boundary[1] : null;
            $sections['ar_first'] = isset($ar_boundary[1]) ? $this->parseHeader($ar_boundary[1]) : null;
            $sections['machine'] = isset($ar_boundary[2]) ? $ar_boundary[2] : null;
            $sections['returned'] = isset($ar_boundary[3]) ? $ar_boundary[3] : null;
        }
        
        return $sections;
    }
    
    /**
     * Function to parse and process the body section machine.
     * @param string $body_section_machine : the body section machine
     * @return array
     */
    private function parseBodySectionMachine($body_section_machine)
    {
        $result = $this->parseDsnFields($body_section_machine);
        $result['mime_header'] = $this->parseLines($result['mime_header']);
        $result['per_message'] = $this->parseLines($result['per_message']);
    
        if(!$this->isEmpty($result['per_message'], 'X-postfix-sender')){
            $ar_tmp = explode(";", $result['per_message']['X-postfix-sender']);
            $result['per_message']['X-postfix-sender'] = array(
                'type'    => isset($ar_tmp[0]) ? trim($ar_tmp[0]) : null,
                'addr'    => isset($ar_tmp[1]) ? trim($ar_tmp[1]) : null,
            );
        }
        if(!$this->isEmpty($result['per_message'], 'Reporting-mta')){
            $ar_tmp = explode(";", $result['per_message']['Reporting-mta']);
            $result['per_message']['Reporting-mta'] = array(
                'type'    => isset($ar_tmp[0]) ? trim($ar_tmp[0]) : null,
                'addr'    => isset($ar_tmp[1]) ? trim($ar_tmp[1]) : null,
            );
        }
        
        $tmp_per_recipient = array();
        foreach($result['per_recipient'] as $per_recipient) {
            $ar_per_recipient = $this->parseLines(explode("\r\n", $per_recipient));
            $ar_per_recipient['Final-recipient'] = isset($ar_per_recipient['Final-recipient']) ? $this->formatFinalRecipient($ar_per_recipient['Final-recipient']) : null;
            $ar_per_recipient['Original-recipient'] = isset($ar_per_recipient['Original-recipient']) ? $this->formatOriginalRecipient($ar_per_recipient['Original-recipient']) : null;
            $ar_per_recipient['Diagnostic-code'] = isset($ar_per_recipient['Diagnostic-code']) ? $this->formatDiagnosticCode($ar_per_recipient['Diagnostic-code']) : null;
            // check if diagnostic code is a temporary failure
            if (isset($ar_per_recipient['Diagnostic-code'])) {
                $status_code = $this->formatStatusCode($ar_per_recipient['Diagnostic-code']['text']);
                $action = $this->findActionByStatusCode($status_code);
                if($action == 'transient' && stristr($ar_per_recipient['Action'], 'failed') !== false) {
                    $ar_per_recipient['Action'] = 'transient';
                    $ar_per_recipient['Status'] = '4.3.0';
                }
            }
            
            $tmp_per_recipient[] = $ar_per_recipient;
        }
        $result['per_recipient'] = $tmp_per_recipient;
        
        return $result;
    }
    
    /**
     * Function to parse DSN fields.
     * @param string $body_section_machine : the body section machine
     * @return array
     */
    private function parseDsnFields($body_section_machine)
    {
        $result = array(
            'mime_header'   => null,
            'per_message'   => null,
            'per_recipient' => null,
        );
        $dsn_fields = explode("\r\n\r\n", $body_section_machine);
        
        $j = 0;
        for ($i = 0; $i < count($dsn_fields); $i++) {
            $dsn_fields[$i] = trim($dsn_fields[$i]);
            if ($i == 0) {
                $result['mime_header'] = $dsn_fields[$i];
            } elseif ($i == 1 && !preg_match('#(Final|Original)-Recipient#', $dsn_fields[$i])) {
                // second element in the array should be a per_recipient
                $result['per_message'] = $dsn_fields[$i];
            } else {
                if ($dsn_fields[$i] == '--') {
                    continue;
                }
                $result['per_recipient'][$j] = $dsn_fields[$i];
                $j++;
            }
        }
        return $result;
    }
    
    /**
     * Function to parse standard fields.
     * @param string $content : a generic content
     * @return array
     */
    private function parseLines($content)
    {
        $result = array();
        if (!is_array($content)) {
            $content = explode("\r\n", $content);
        }
        foreach ($content as $line) {
            if (preg_match('#^([^\s.]*):\s*(.*)\s*#', $line, $matches)) {
                $entity = ucfirst(strtolower($matches[1]));
                if (empty($result[$entity])) {
                    $result[$entity] = trim($matches[2]);
                } elseif (isset($hash['Received']) && $hash['Received']) {
                    if ($entity && $matches[2] && $matches[2] != $hash[$entity]){
                        $result[$entity] .= "|" . trim($matches[2]);
                    }
                }
            } elseif (preg_match('/^\s+(.+)\s*/', $line) && isset($entity)) {
                $result[$entity] .= ' ' . $line;
            }
        }
        return $result;
    }
    
    /**
     * Function to check if a message is a bounce via headers informations.
     * @param array $ar_header : the array headers
     * @return boolean
     */
    private function isBounce($ar_header)
    {
        if (!empty($ar_header)) {
            if (isset($ar_header['Subject']) && preg_match('#(mail delivery failed|failure notice|warning: message|delivery status notif|delivery failure|delivery problem|spam eater|returned mail|undeliverable|returned mail|delivery errors|mail status report|mail system error|failure delivery|delivery notification|delivery has failed|undelivered mail|returned email|returning message to sender|returned to sender|message delayed|mdaemon notification|mailserver notification|mail delivery system|nondeliverable mail|mail transaction failed)|auto.{0,20}reply|vacation|(out|away|on holiday).*office#i', $ar_header['Subject'])) {
                return true;
            } elseif (isset($ar_header['Precedence']) && preg_match('#auto_reply#', $ar_header['Precedence'])) {
                return true;
            } elseif (isset($ar_header['From']) && preg_match('#auto_reply#', $ar_header['From'])) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Function to check if a message is a feedback loop via headers and body informations.
     * @param array $ar_header : the array headers
     * @param array $body_sections : the array body sections
     * @return boolean
     */
    private function isFbl($ar_header, $body_sections=array())
    {
        if (!empty($ar_header)) {
            if (isset($ar_header['Content-type']) && isset($ar_header['Content-type']['report-type']) && preg_match('#feedback-report#', $ar_header['Content-type']['report-type'])) {
                return true;
            } elseif (isset($ar_header['X-loop']) && preg_match('#scomp#', $ar_header['X-loop'])) {
                return true;
            } elseif ($this->isHotmailFbl($body_sections)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Function to check if a message is a hotmail feedback loop via body informations.
     * @param array $body_sections : the array body sections
     * @return boolean
     */
    private function isHotmailFbl($body_sections)
    {
        return !empty($body_sections) && isset($body_sections['ar_first']) && isset($body_sections['ar_first']['X-HmXmrOriginalRecipient']);
    }
    
    /**
     * Function to check if a message is a specific RFC 1892 report via headers informations.
     * @param array $ar_header : the array headers
     * @return boolean
     */
    private function isRfc1892Report($ar_header)
    {
        if (!empty($ar_header)) {
            if (!$this->isEmpty($ar_header, 'Content-type') && !$this->isEmpty($ar_header['Content-type'], 'type') && $ar_header['Content-type']['type'] == 'multipart/report') {
                if (!$this->isEmpty($ar_header['Content-type'], 'report-type') && $ar_header['Content-type']['report-type'] == 'delivery-status') {
                    if (!$this->isEmpty($ar_header['Content-type'], 'boundary') && $ar_header['Content-type']['boundary'] !== '') {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Format the content of a message.
     * @param string $content : a generic content
     * @return string
     */
    private static function formatContent($content)
    {
        if (!empty($content)) {
            $content = str_replace("\r\n", "\n", $content);
            $content = str_replace("\n", "\r\n", $content);
            $content = str_replace("=\r\n", "",  $content);
            $content = str_replace("=3D", "=",   $content);
            $content = str_replace("=09", "  ",  $content);
        }
        return $content;
    }
    
    /**
     * Format the final recipient with e-mail and type
     * @param string $final_recipient : the final recipient
     * @return array
     */
    private function formatFinalRecipient($final_recipient){
        $result = array(
            'addr'    => '',
            'type'    => '',
        );
        
        $ar_final_recipient = explode(";", $final_recipient);
        if (!empty($ar_final_recipient)) {
            if (strpos($ar_final_recipient[0], '@') !== false) {
                $result['addr'] = $this->extractEmail($ar_final_recipient[0]);
                $result['type'] = !$this->isEmpty($ar_final_recipient, 1) ? trim($ar_final_recipient[1]) : 'unknown';
            } else {
                $result['addr'] = $this->extractEmail($ar_final_recipient[1]);
                $result['type'] = !$this->isEmpty($ar_final_recipient, 0) ? trim($ar_final_recipient[0]) : '';
            }
        }
        
        return $result;
    }
    
    /**
     * Format the original recipient with e-mail and type
     * @param string $original_recipient : the original recipient
     * @return array
     */
    private function formatOriginalRecipient($original_recipient){
        $result = array(
            'addr'    => '',
            'type'    => '',
        );
    
        $ar_original_recipient = explode(";", $original_recipient);
        if (!empty($ar_original_recipient)) {
            $result['addr'] = $this->extractEmail($ar_original_recipient[1]);
            $result['type'] = !$this->isEmpty($ar_original_recipient, 0) ? trim($ar_original_recipient[0]) : '';
        }
    
        return $result;
    }
    
    /**
     * Format the diagnostic code with type and text
     * @param string $diag_code : the diagnostic recipient
     * @return array
     */
    private function formatDiagnosticCode($diag_code){
        $result = array(
            'type'    => '',
            'text'    => '',
        );
    
        $ar_diag_code = explode(";", $diag_code);
        if (!empty($ar_diag_code)) {
            $result['type'] = !$this->isEmpty($ar_diag_code, 0) ? trim($ar_diag_code[0]) : '';
            $result['text'] = !$this->isEmpty($ar_diag_code, 1) ? trim($ar_diag_code[1]) : '';
        }
    
        return $result;
    }
    
    /**
     * Find the recipient e-mail
     * @param string $rcpt : the recipient headers
     * @return string
     */
    private function findEmail($rcpt)
    {
        if(isset($rcpt['Original-recipient']) && !$this->isEmpty($rcpt['Original-recipient'], 'addr')){
            return $this->extractEmail($rcpt['Original-recipient']['addr']);
        } elseif(isset($rcpt['Final-recipient']) && !$this->isEmpty($rcpt['Final-recipient'], 'addr')){
            return $this->extractEmail($rcpt['Final-recipient']['addr']);
        }
        return null;
    }
    
    /**
     * Find the e-mail(s) from the body section first
     * @param string $body_section_first : the body section first
     * @return array
     */
    private function findEmails($body_section_first)
    {
        $result = array();
        if (!empty($body_section_first)) {
            $ar_body_section_first = explode("\r\n", $body_section_first);
            foreach ($ar_body_section_first as $line) {
                if (preg_match("/\b([A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/i", $line, $match)) {
                    if (!in_array($match[1], $result)) {
                        $result[] = $match[1];
                    }
                }
            }
        }
        return $result;
    }
    
    /**
     * Find an action by the status code.
     * @param string $status_code : the status code
     * @return string
     */
    private function findActionByStatusCode($status_code)
    {
        $result = null;
        if (!empty($status_code)) {
            $status_code = $this->formatStatusCode($status_code);
            $ar_status_code = explode(".", $status_code);
            
            switch ($ar_status_code[0]) {
                case '2' :
                    $result = 'success';
                    break;
                case '4' :
                    $result = 'transient';
                    break;
                case '5' :
                    $result = 'failed';
                    break;
                default:
                    return '';
                    break;
            }
        }
        if (!empty($result)) {
            $this->output('Action type <strong>' . $result . '</strong> found via status code', CWSMBH_VERBOSE_DEBUG);
        }
        return $result;
    }
    
    /**
     * Find an status code in body content.
     * @param string $body : the body
     * @return string
     */
    private function findStatusCodeByRecipient($body)
    {
        $ar_body = explode("\r\n", $body);
    
        foreach ($ar_body as $body_line) {
            $body_line = trim($body_line);
    
            foreach ($this->_status_code_resolver as $bounce_body => $bounce_code) {
                if (preg_match('#' . $bounce_body . '#is', $body_line, $matches)) {
                    $status_code = isset($matches[1]) ? $matches[1] : $bounce_code;
                    $status_code = $this->formatStatusCode($status_code);
                    $this->output('Status code <strong>' . $status_code . '</strong> found via code resolver pattern : <i>' . $bounce_body . '</i>', CWSMBH_VERBOSE_DEBUG);
                    return $status_code;
                }
            }
    
            // RFC 1893 (http://www.ietf.org/rfc/rfc1893.txt) return code
            if (preg_match('#\W([245]\.[01234567]\.[012345678])\W#', $body_line, $matches)) {
                if (stripos($body_line, 'Message-ID') !== false) {
                    break;
                }
                $status_code = $matches[1];
                $status_code = $this->formatStatusCode($status_code);
                $this->output('Status code <strong>' . $status_code . '</strong> found via RFC 1893.', CWSMBH_VERBOSE_DEBUG);
                return $status_code;
            }
    
            // RFC 821 (http://www.ietf.org/rfc/rfc821.txt) return code
            if (preg_match('#\]?: ([45][01257][012345]) #', $body_line, $matches) || preg_match('#^([45][01257][012345]) (?:.*?)(?:denied|inactive|deactivated|rejected|disabled|unknown|no such|not (?:our|activated|a valid))+#i', $body_line, $matches)) {
                $status_code = $matches[1];
                // map to new RFC
                if ($status_code == '450' || $status_code == '550' || $status_code == '551' || $status_code == '554') {
                    $status_code = '511';
                } elseif ($status_code == '452' || $status_code == '552') {
                    $status_code = '422';
                } elseif ($status_code == '421') {
                    $status_code = '432';
                }
                $status_code = $this->formatStatusCode($status_code);
                $this->output('Status code <strong>' . $status_code . '</strong> found and converted via RFC 821.', CWSMBH_VERBOSE_DEBUG);
                return $status_code;
            }
        }
    
        return null;
    }
    
    /**
     * Get explanations from DSN status code via the RFC 1893 : http://www.ietf.org/rfc/rfc1893.txt
     * @param string $status_code : consist of three numerical fields separated by ".".
     * @return array $result : an array include the following fields : 'code', 'first_subcode', 'second_subcode', 'third_subcode'.
     */
    public function findStatusExplanationsByCode($status_code)
    {
        $result = $this->_status_result;
        $status_code = $this->formatStatusCode($status_code);
        
        if (!$this->isEmpty($status_code)) {
            $ar_status_code = explode(".", $status_code);
            if ($ar_status_code != null && count($ar_status_code) == 3) {
                $result['code'] = $status_code;
                
                // First sub-code : indicates whether the delivery attempt was successful
                switch ($ar_status_code[0]) {
                    case '2' :
                        $result[CWSMBH_STATUS_FIRST_SUBCODE] = array(
                            'title'    => 'Success',
                            'desc'     => 'Success specifies that the DSN is reporting a positive delivery action. Detail sub-codes may provide notification of transformations required for delivery.',
                        );
                        break;
    
                    case '4' :
                        $result[CWSMBH_STATUS_FIRST_SUBCODE] = array(
                            'title'    => 'Persistent Transient Failure',
                            'desc'     => 'A persistent transient failure is one in which the message as sent is valid, but some temporary event prevents the successful sending of the message. Sending in the future may be successful.',
                        );
                        break;
    
                    case '5' :
                        $result[CWSMBH_STATUS_FIRST_SUBCODE] = array(
                            'title'    => 'Permanent Failure',
                            'desc'     => 'A permanent failure is one which is not likely to be resolved by resending the message in the current form. Some change to the message or the destination must be made for successful delivery.',
                        );
                        break;
    
                    default :
                        break;
                }
    
                // Second sub-code : indicates the probable source of any delivery anomalies
                switch ($ar_status_code[1]) {
                    case '0' :
                        $result[CWSMBH_STATUS_SECOND_SUBCODE] = array(
                            'title'    => 'Other or Undefined Status',
                            'desc'     => 'There is no additional subject information available.',
                        );
                        break;
    
                    case '1' :
                        $result[CWSMBH_STATUS_SECOND_SUBCODE] = array(
                            'title'    => 'Addressing Status',
                            'desc'     => 'The address status reports on the originator or destination address. It may include address syntax or validity. These errors can generally be corrected by the sender and retried.',
                        );
                        break;
    
                    case '2' :
                        $result[CWSMBH_STATUS_SECOND_SUBCODE] = array(
                            'title'    => 'Mailbox Status',
                            'desc'     => 'Mailbox status indicates that something having to do with the mailbox has cause this DSN. Mailbox issues are assumed to be under the general control of the recipient.',
                        );
                        break;
    
                    case '3' :
                        $result[CWSMBH_STATUS_SECOND_SUBCODE] = array(
                            'title'    => 'Mail System Status',
                            'desc'     => 'Mail system status indicates that something having to do with the destination system has caused this DSN. System issues are assumed to be under the general control of the destination system administrator.',
                        );
                        break;
    
                    case '4' :
                        $result[CWSMBH_STATUS_SECOND_SUBCODE] = array(
                            'title'    => 'Network and Routing Status',
                            'desc'     => 'The networking or routing codes report status about the delivery system itself. These system components include any necessary infrastructure such as directory and routing services. Network issues are assumed to be under the control of the destination or intermediate system administrator.',
                        );
                        break;
    
                    case '5' :
                        $result[CWSMBH_STATUS_SECOND_SUBCODE] = array(
                            'title'    => 'Mail Delivery Protocol Status',
                            'desc'     => 'The mail delivery protocol status codes report failures involving the message delivery protocol. These failures include the full range of problems resulting from implementation errors or an unreliable connection. Mail delivery protocol issues may be controlled by many parties including the originating system, destination system, or intermediate system administrators.',
                        );
                        break;
    
                    case '6' :
                        $result[CWSMBH_STATUS_SECOND_SUBCODE] = array(
                            'title'    => 'Message Content or Media Status',
                            'desc'     => 'The message content or media status codes report failures involving the content of the message. These codes report failures due to translation, transcoding, or otherwise unsupported message media. Message content or media issues are under the control of both the sender and the receiver, both of whom must support a common set of supported content-types.',
                        );
                        break;
    
                    case '7' :
                        $result[CWSMBH_STATUS_SECOND_SUBCODE] = array(
                            'title'    => 'Security or Policy Status',
                            'desc'     => 'The security or policy status codes report failures involving policies such as per-recipient or per-host filtering and cryptographic operations. Security and policy status issues are assumed to be under the control of either or both the sender and recipient. Both the sender and recipient must permit the exchange of messages and arrange the exchange of necessary keys and certificates for cryptographic operations.',
                        );
                        break;
    
                    default :
                        break;
                }
    
                // Second and Third sub-code : indicates a precise error condition
                switch ($ar_status_code[1] . '.' . $ar_status_code[2]) {
                    case '0.0' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Other undefined Status',
                            'desc'     => 'Other undefined status is the only undefined error code. It should be used for all errors for which only the class of the error is known.',
                        );
                        break;
    
                    case '1.0' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Other address status',
                            'desc'     => 'Something about the address specified in the message caused this DSN.',
                        );
                        break;
    
                    case '1.1' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Bad destination mailbox address',
                            'desc'     => 'The mailbox specified in the address does not exist. For Internet mail names, this means the address portion to the left of the @ sign is invalid. This code is only useful for permanent failures.',
                        );
                        break;
    
                    case '1.2' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Bad destination system address',
                            'desc'     => 'The destination system specified in the address does not exist or is incapable of accepting mail. For Internet mail names, this means the address portion to the right of the @ is invalid for mail. This codes is only useful for permanent failures.',
                        );
                        break;
    
                    case '1.3' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Bad destination mailbox address syntax',
                            'desc'     => 'The destination address was syntactically invalid. This can apply to any field in the address. This code is only useful for permanent failures.',
                        );
                        break;
    
                    case '1.4' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Destination mailbox address ambiguous',
                            'desc'     => 'The mailbox address as specified matches one or more recipients on the destination system. This may result if a heuristic address mapping algorithm is used to map the specified address to a local mailbox name.',
                        );
                        break;
    
                    case '1.5' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Destination address valid',
                            'desc'     => 'This mailbox address as specified was valid. This status code should be used for positive delivery reports.',
                        );
                        break;
    
                    case '1.6' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Destination mailbox has moved, No forwarding address',
                            'desc'     => 'The mailbox address provided was at one time valid, but mail is no longer being accepted for that address. This code is only useful for permanent failures.',
                        );
                        break;
    
                    case '1.7' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Bad sender\'s mailbox address syntax',
                            'desc'     => 'The sender\'s address was syntactically invalid. This can apply to any field in the address.',
                        );
                        break;
    
                    case '1.8' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Bad sender\'s system address',
                            'desc'     => 'The sender\'s system specified in the address does not exist or is incapable of accepting return mail. For domain names, this means the address portion to the right of the @ is invalid for mail.',
                        );
                        break;
    
                    case '2.0' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Other or undefined mailbox status',
                            'desc'     => 'The mailbox exists, but something about the destination mailbox has caused the sending of this DSN.',
                        );
                        break;
    
                    case '2.1' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Mailbox disabled, not accepting messages',
                            'desc'     => 'The mailbox exists, but is not accepting messages. This may be a permanent error if the mailbox will never be re-enabled or a transient error if the mailbox is only temporarily disabled.',
                        );
                        break;
    
                    case '2.2' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Mailbox full',
                            'desc'     => 'The mailbox is full because the user has exceeded a per-mailbox administrative quota or physical capacity. The general semantics implies that the recipient can delete messages to make more space available. This code should be used as a persistent transient failure.',
                        );
                        break;
    
                    case '2.3' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Message length exceeds administrative limit',
                            'desc'     => 'A per-mailbox administrative message length limit has been exceeded. This status code should be used when the per-mailbox message length limit is less than the general system limit. This code should be used as a permanent failure.',
                        );
                        break;
    
                    case '2.4' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Mailing list expansion problem',
                            'desc'     => 'The mailbox is a mailing list address and the mailing list was unable to be expanded. This code may represent a permanent failure or a persistent transient failure.',
                        );
                        break;
    
                    case '3.0' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Other or undefined mail system status',
                            'desc'     => 'The destination system exists and normally accepts mail, but something about the system has caused the generation of this DSN.',
                        );
                        break;
    
                    case '3.1' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Mail system full',
                            'desc'     => 'Mail system storage has been exceeded. The general semantics imply that the individual recipient may not be able to delete material to make room for additional messages. This is useful only as a persistent transient error.',
                        );
                        break;
    
                    case '3.2' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'System not accepting network messages',
                            'desc'     => 'The host on which the mailbox is resident is not accepting messages. Examples of such conditions include an immanent shutdown, excessive load, or system maintenance. This is useful for both permanent and permanent transient errors.',
                        );
                        break;
    
                    case '3.3' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'System not capable of selected features',
                            'desc'     => 'Selected features specified for the message are not supported by the destination system. This can occur in gateways when features from one domain cannot be mapped onto the supported feature in another.',
                        );
                        break;
    
                    case '3.4' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Message too big for system',
                            'desc'     => 'The message is larger than per-message size limit. This limit may either be for physical or administrative reasons. This is useful only as a permanent error.',
                        );
                        break;
    
                    case '3.5' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'System incorrectly configured',
                            'desc'     => 'The system is not configured in a manner which will permit it to accept this message.',
                        );
                        break;
    
                    case '4.0' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Other or undefined network or routing status',
                            'desc'     => 'Something went wrong with the networking, but it is not clear what the problem is, or the problem cannot be well expressed with any of the other provided detail codes.',
                        );
                        break;
    
                    case '4.1' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'No answer from host',
                            'desc'     => 'The outbound connection attempt was not answered, either because the remote system was busy, or otherwise unable to take a call. This is useful only as a persistent transient error.',
                        );
                        break;
    
                    case '4.2' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Bad connection',
                            'desc'     => 'The outbound connection was established, but was otherwise unable to complete the message transaction, either because of time-out, or inadequate connection quality. This is useful only as a persistent transient error.',
                        );
                        break;
    
                    case '4.3' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Directory server failure',
                            'desc'     => 'The network system was unable to forward the message, because a directory server was unavailable. This is useful only as a persistent transient error. The inability to connect to an Internet DNS server is one example of the directory server failure error.',
                        );
                        break;
    
                    case '4.4' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Unable to route',
                            'desc'     => 'The mail system was unable to determine the next hop for the message because the necessary routing information was unavailable from the directory server. This is useful for both permanent and persistent transient errors. A DNS lookup returning only an SOA (Start of Administration) record for a domain name is one example of the unable to route error.',
                        );
                        break;
    
                    case '4.5' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Mail system congestion',
                            'desc'     => 'The mail system was unable to deliver the message because the mail system was congested. This is useful only as a persistent transient error.',
                        );
                        break;
    
                    case '4.6' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Routing loop detected',
                            'desc'     => 'A routing loop caused the message to be forwarded too many times, either because of incorrect routing tables or a user forwarding loop. This is useful only as a persistent transient error.',
                        );
                        break;
    
                    case '4.7' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Delivery time expired',
                            'desc'     => 'The message was considered too old by the rejecting system, either because it remained on that host too long or because the time-to-live value specified by the sender of the message was exceeded. If possible, the code for the actual problem found when delivery was attempted should be returned rather than this code. This is useful only as a persistent transient error.',
                        );
                        break;
    
                    case '5.0' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Other or undefined protocol status',
                            'desc'     => 'Something was wrong with the protocol necessary to deliver the message to the next hop and the problem cannot be well expressed with any of the other provided detail codes.',
                        );
                        break;
    
                    case '5.1' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Invalid command',
                            'desc'     => 'A mail transaction protocol command was issued which was either out of sequence or unsupported. This is useful only as a permanent error.',
                        );
                        break;
    
                    case '5.2' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Syntax error',
                            'desc'     => 'A mail transaction protocol command was issued which could not be interpreted, either because the syntax was wrong or the command is unrecognized. This is useful only as a permanent error.',
                        );
                        break;
    
                    case '5.3' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Too many recipients',
                            'desc'     => 'More recipients were specified for the message than could have been delivered by the protocol. This error should normally result in the segmentation of the message into two, the remainder of the recipients to be delivered on a subsequent delivery attempt. It is included in this list in the event that such segmentation is not possible.',
                        );
                        break;
    
                    case '5.4' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Invalid command arguments',
                            'desc'     => 'A valid mail transaction protocol command was issued with invalid arguments, either because the arguments were out of range or represented unrecognized features. This is useful only as a permanent error.',
                        );
                        break;
    
                    case '5.5' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Wrong protocol version',
                            'desc'     => 'A protocol version mis-match existed which could not be automatically resolved by the communicating parties.',
                        );
                        break;
    
                    case '6.0' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Other or undefined media error',
                            'desc'     => 'Something about the content of a message caused it to be considered undeliverable and the problem cannot be well expressed with any of the other provided detail codes.',
                        );
                        break;
    
                    case '6.1' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Media not supported',
                            'desc'     => 'The media of the message is not supported by either the delivery protocol or the next system in the forwarding path. This is useful only as a permanent error.',
                        );
                        break;
    
                    case '6.2' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Conversion required and prohibited',
                            'desc'     => 'The content of the message must be converted before it can be delivered and such conversion is not permitted. Such prohibitions may be the expression of the sender in the message itself or the policy of the sending host.',
                        );
                        break;
    
                    case '6.3' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Conversion required but not supported',
                            'desc'     => 'The message content must be converted to be forwarded but such conversion is not possible or is not practical by a host in the forwarding path. This condition may result when an ESMTP gateway supports 8bit transport but is not able to downgrade the message to 7 bit as required for the next hop.',
                        );
                        break;
    
                    case '6.4' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Conversion with loss performed',
                            'desc'     => 'This is a warning sent to the sender when message delivery was successfully but when the delivery required a conversion in which some data was lost. This may also be a permanant error if the sender has indicated that conversion with loss is prohibited for the message.',
                        );
                        break;
    
                    case '6.5' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Conversion Failed',
                            'desc'     => 'A conversion was required but was unsuccessful. This may be useful as a permanent or persistent temporary notification.',
                        );
                        break;
    
                    case '7.0' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Other or undefined security status',
                            'desc'     => 'Something related to security caused the message to be returned, and the problem cannot be well expressed with any of the other provided detail codes. This status code may also be used when the condition cannot be further described because of security policies in force.',
                        );
                        break;
    
                    case '7.1' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Delivery not authorized, message refused',
                            'desc'     => 'The sender is not authorized to send to the destination. This can be the result of per-host or per-recipient filtering. This memo does not discuss the merits of any such filtering, but provides a mechanism to report such. This is useful only as a permanent error.',
                        );
                        break;
    
                    case '7.2' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Mailing list expansion prohibited',
                            'desc'     => 'The sender is not authorized to send a message to the intended mailing list. This is useful only as a permanent error.',
                        );
                        break;
    
                    case '7.3' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Security conversion required but not possible',
                            'desc'     => 'A conversion from one secure messaging protocol to another was required for delivery and such conversion was not possible. This is useful only as a permanent error.',
                        );
                        break;
    
                    case '7.4' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Security features not supported',
                            'desc'     => 'A message contained security features such as secure authentication which could not be supported on the delivery protocol. This is useful only as a permanent error.',
                        );
                        break;
    
                    case '7.5' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Cryptographic failure',
                            'desc'     => 'A transport system otherwise authorized to validate or decrypt a message in transport was unable to do so because necessary information such as key was not available or such information was invalid.',
                        );
                        break;
    
                    case '7.6' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                            'title'    => 'Cryptographic algorithm not supported',
                            'desc'     => 'A transport system otherwise authorized to validate or decrypt a message was unable to do so because the necessary algorithm was not supported.',
                        );
                        break;
    
                    case '7.7' :
                        $result[CWSMBH_STATUS_THIRD_SUBCODE] = array(
                        'title'    => 'Message integrity failure',
                        'desc'     => 'A transport system otherwise authorized to validate a message was unable to do so because the message was corrupted or altered. This may be useful as a permanent, transient persistent, or successful delivery code.',
                        );
                        break;
    
                    default :
                        break;
                }
            }
        }
        return $result;
    }
    
    private static function endWith($string, $search)
    {
        $length = strlen($search);
        $start  = $length * -1;
        return (substr($string, $start) === $search);
    }
    
    private static function isEmpty($value, $key='')
    {
        if (!empty($key) && is_array($value)) {
            return !array_key_exists($key, $value) || empty($value[$key]);
        } else {
            return !isset($value) || empty($value);
        }
    }
    
    private static function extractEmail($string)
    {
        $result = $string;
        $ar_result = preg_split('#[ \"\'\<\>:\(\)\[\]]#', $string);
        foreach ($ar_result as $result){
            if (strpos($result, '@') !== false){
                return $result;
            }
        }
        return $result;
    }
    
    private static function formatStatusCode($status_code)
    {
        if (!empty($status_code)) {
            if (preg_match('#(\d\d\d)\s#', $status_code, $match)) {
                $status_code = $match[1];
            } elseif (preg_match('#(\d\.\d\.\d)\s#', $status_code, $match)) {
                $status_code = $match[1];
            }
            if (preg_match('#([245]\.[01234567]\.[012345678])(.*)#', $status_code, $match)) {
                return $match[1];
            } elseif (preg_match('#([245][01234567][012345678])(.*)#', $status_code, $match)) {
                preg_match_all('#.#', $match[1], $ar_status_code);
                if (is_array($ar_status_code[0]) && count($ar_status_code[0]) == 3) {
                    return implode(".", $ar_status_code[0]);
                }
            }
        }
        return null;
    }
}

?>
