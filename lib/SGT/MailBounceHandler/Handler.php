<?php


namespace SGT\MailBounceHandler;

use Cws\CwsDebug;
use SGT\MailBounceHandler\Models\Mail;
use SGT\MailBounceHandler\Models\Recipient;

class Handler
{
    const OPEN_MODE_MAILBOX = 'mailbox'; // if you open a mailbox via imap.
    const OPEN_MODE_FILE = 'file'; // if you open a eml file or a folder containing eml files.

    const PROCESS_MODE_NEUTRAL = 'neutral'; // messages will not be processed.
    const PROCESS_MODE_MOVE = 'move'; // if you want to move bounces.
    const PROCESS_MODE_DELETE = 'delete'; // if you want to delete bounces.

    const MAILBOX_SERVICE_IMAP = 'imap';
    const MAILBOX_SERVICE_POP3 = 'pop3';

    const MAILBOX_PORT_POP3 = 110;
    const MAILBOX_PORT_POP3_TLS_SSL = 995;
    const MAILBOX_PORT_IMAP = 143;
    const MAILBOX_PORT_IMAP_TLS_SSL = 993;

    const MAILBOX_SECURITY_NONE = 'none';
    const MAILBOX_SECURITY_NOTLS = 'notls';
    const MAILBOX_SECURITY_TLS = 'tls';
    const MAILBOX_SECURITY_SSL = 'ssl';

    const MAILBOX_CERT_NOVALIDATE = 'novalidate-cert'; // do not validate certificates from TLS/SSL server.
    const MAILBOX_CERT_VALIDATE = 'validate-cert'; // validate certificates from TLS/SSL server.

    const SUFFIX_BOUNCES_MOVE = 'bounces'; // suffix of mailbox or folder to move bounces to.

    const TYPE_BOUNCE = 'bounce'; // message is a bounce
    const TYPE_FBL = 'fbl'; // message is a feedback loop

    const BOUNCE_AUTOREPLY = 'autoreply';
    const BOUNCE_BLOCKED = 'blocked';
    const BOUNCE_GENERIC = 'generic';
    const BOUNCE_HARD = 'hard';
    const BOUNCE_SOFT = 'soft';
    const BOUNCE_TEMPORARY = 'temporary';

    const CAT_ANTISPAM = 'antispam';
    const CAT_AUTOREPLY = 'autoreply';
    const CAT_CONCURRENT = 'concurrent';
    const CAT_CONTENT_REJECT = 'content_reject';
    const CAT_COMMAND_REJECT = 'command_reject';
    const CAT_DEFER = 'defer';
    const CAT_DELAYED = 'delayed';
    const CAT_DNS_LOOP = 'dns_loop';
    const CAT_DNS_UNKNOWN = 'dns_unknown';
    const CAT_FULL = 'full';
    const CAT_INACTIVE = 'inactive';
    const CAT_INTERNAL_ERROR = 'internal_error';
    const CAT_LATIN_ONLY = 'latin_only';
    const CAT_OTHER = 'other';
    const CAT_OVERSIZE = 'oversize';
    const CAT_TIMEOUT = 'timeout';
    const CAT_UNKNOWN = 'unknown';
    const CAT_UNRECOGNIZED = 'unrecognized';
    const CAT_USER_REJECT = 'user_reject';
    const CAT_WARNING = 'warning';

    const STATUS_CODE = 'code';
    const STATUS_FIRST_SUBCODE = 'first_subcode';
    const STATUS_SECOND_SUBCODE = 'second_subcode';
    const STATUS_THIRD_SUBCODE = 'third_subcode';

    /**
     * Control the method to open e-mail(s).
     *
     * @var string
     */
    protected $openMode;

    /**
     * Control the method to process bounces.
     * default PROCESS_MODE_NEUTRAL.
     *
     * @var string
     */
    protected $processMode;

    /**
     * Defines mailbox service, MAILBOX_SERVICE_POP3 or MAILBOX_SERVICE_IMAP
     * default MAILBOX_SERVICE_IMAP.
     *
     * @var string
     */
    protected $mailboxService;

    /**
     * Mailbox host server.<br />
     * default 'localhost'.
     *
     * @var string
     */
    protected $mailboxHost;

    /**
     * The username of mailbox.
     *
     * @var string
     */
    protected $mailboxUsername;

    /**
     * The password needed to access mailbox.
     *
     * @var string
     */
    protected $mailboxPassword;

    /**
     * Defines port number, other common choices are MAILBOX_PORT_IMAP, MAILBOX_PORT_IMAP_TLS_SSL
     * default MAILBOX_PORT_IMAP.
     *
     * @var int
     */
    protected $mailboxPort;

    /**
     * Defines service option, choices are MAILBOX_SECURITY_NONE, MAILBOX_SECURITY_NOTLS, MAILBOX_SECURITY_TLS,
     * MAILBOX_SECURITY_SSL. default MAILBOX_SECURITY_NOTLS.
     *
     * @var string
     */
    protected $mailboxSecurity;

    /**
     * Control certificates validation if mailSecurity is MAILBOX_SECURITY_TLS or MAILBOX_SECURITY_SSL.
     * default MAILBOX_CERT_NOVALIDATE.
     *
     * @var string
     */
    protected $mailboxCert;

    /**
     * Mailbox type, other choices are (Tasks, Spam, Replies, etc.)
     * default 'INBOX'.
     *
     * @var string
     */
    protected $mailboxName = 'INBOX';

    /**
     * The resource handler for the opened mailbox (POP3/IMAP/NNTP/etc.).
     *
     * @var resource
     */
    protected $mailboxHandler = false;

    /**
     * Maximum limit messages processed in one batch (0 for unlimited).
     * default 0.
     *
     * @var int
     */
    protected $maxMessages = 0;

    /**
     * Purge unknown messages. Be careful with this option.
     * default false.
     *
     * @var bool
     */
    protected $purge = false;

    /**
     * The folder path opened.
     *
     * @var string
     */
    protected $emlFolder;

    /**
     * The eml files opened.
     *
     * @var array
     */
    protected $emlFiles;

    /**
     * Control the move mode.
     *
     * @var bool
     */
    protected $enableMove;

    /**
     * The last error message.
     *
     * @var string
     */
    protected $error;

    /**
     * The cws debug instance.
     *
     * @var CwsDebug
     */
    protected $cwsDebug;

    public function __construct(CwsDebug $cwsDebug)
    {
        $this->cwsDebug = $cwsDebug;
        $this->processMode = self::PROCESS_MODE_NEUTRAL;
        $this->purge = false;
    }

    protected function reset()
    {
        $this->mailboxHandler = false;
        $this->emlFolder = '';
        $this->emlFiles = array();
        $this->enableMove = true;
    }

    /**
     * Function to process parsing of each individual message.
     *
     * @param string $token : message number or filename.
     * @param string $content : message content.
     *
     * @return Mail
     */
    protected function processMailParsing($token, $content)
    {
        $cwsMbhMail = new Mail();
        $cwsMbhMail->setToken($token);

        // format content
        $content = self::formatEmailContent($content);

        // split head and body
        if (preg_match('#\r\n\r\n#is', $content)) {
            list($header, $body) = preg_split('#\r\n\r\n#', $content, 2);
        } else {
            list($header, $body) = preg_split('#\n\n#', $content, 2);
        }

        $cwsMbhMail->setHeader($header);
        $cwsMbhMail->setBody($body);

        // parse header
        $header = self::parseHeader($header);

        // parse body sections
        $bodySections = self::parseBodySections($header, $body);

        // check bounce and fbl
        $isBounce = self::isBounce($header);
        $isFbl = self::isFbl($header, $bodySections);

        if ($isBounce) {
            $cwsMbhMail->setType(self::TYPE_BOUNCE);
        } elseif ($isFbl) {
            $cwsMbhMail->setType(self::TYPE_FBL);
        }

        // begin process
        $tmpRecipients = array();
        if ($isFbl) {
            $this->cwsDebug->simple('<strong>Feedback loop</strong> detected', CwsDebug::VERBOSE_DEBUG);
            $cwsMbhMail->setSubject(trim(str_ireplace('Fw:', '', $header['Subject'])));

            if (self::isHotmailFbl($bodySections)) {
                $this->cwsDebug->simple('This message is an <strong>Hotmail fbl</strong>', CwsDebug::VERBOSE_DEBUG);
                $bodySections['arMachine']['Content-disposition'] = 'inline';
                $bodySections['arMachine']['Content-type'] = 'message/feedback-report';
                $bodySections['arMachine']['Feedback-type'] = 'abuse';
                $bodySections['arMachine']['User-agent'] = 'Hotmail FBL';
                if (!self::isEmpty($bodySections['arFirst'], 'Date')) {
                    $bodySections['arMachine']['Received-date'] = $bodySections['arFirst']['Date'];
                }
                if (!self::isEmpty($bodySections['arFirst'], 'X-HmXmrOriginalRecipient')) {
                    $bodySections['arMachine']['Original-rcpt-to'] = $bodySections['arFirst']['X-HmXmrOriginalRecipient'];
                }
                if (!self::isEmpty($bodySections['arFirst'], 'X-sid-pra')) {
                    $bodySections['arMachine']['Original-mail-from'] = $bodySections['arFirst']['X-sid-pra'];
                }
            } else {
                if (!self::isEmpty($bodySections, 'machine')) {
                    $bodySections['arMachine'] = self::parseLines($bodySections['machine']);
                }
                if (!self::isEmpty($bodySections, 'returned')) {
                    $bodySections['arReturned'] = self::parseLines($bodySections['returned']);
                }
                if (self::isEmpty($bodySections['arMachine'],
                        'Original-mail-from') && !self::isEmpty($bodySections['arReturned'], 'From')) {
                    $bodySections['arMachine']['Original-mail-from'] = $bodySections['arReturned']['From'];
                }
                if (self::isEmpty($bodySections['arMachine'],
                        'Original-rcpt-to') && !self::isEmpty($bodySections['arMachine'], 'Removal-recipient')) {
                    $bodySections['arMachine']['Original-rcpt-to'] = $bodySections['arMachine']['Removal-recipient'];
                } elseif (!self::isEmpty($bodySections['arReturned'], 'To')) {
                    $bodySections['arMachine']['Original-rcpt-to'] = $bodySections['arReturned']['To'];
                }
                // try to get the actual intended recipient if possible
                if (preg_match('#Undisclosed|redacted#i',
                        $bodySections['arMachine']['Original-mail-from']) && !self::isEmpty($bodySections['arMachine'],
                        'Removal-recipient')) {
                    $bodySections['arMachine']['Original-rcpt-to'] = $bodySections['arMachine']['Removal-recipient'];
                }
                if (self::isEmpty($bodySections['arMachine'],
                        'Received-date') && !self::isEmpty($bodySections['arMachine'], 'Arrival-date')) {
                    $bodySections['arMachine']['Received-date'] = $bodySections['arMachine']['Arrival-date'];
                }
                if (!self::isEmpty($bodySections['arMachine'], 'Original-mail-from')) {
                    $bodySections['arMachine']['Original-mail-from'] = self::extractEmail($bodySections['arMachine']['Original-mail-from']);
                }
                if (!self::isEmpty($bodySections['arMachine'], 'Original-rcpt-to')) {
                    $bodySections['arMachine']['Original-rcpt-to'] = self::extractEmail($bodySections['arMachine']['Original-rcpt-to']);
                }
            }

            $cwsMbhRecipient = new Recipient();
            $cwsMbhRecipient->setAction('failed');
            $cwsMbhRecipient->setStatus('5.7.1');
            $cwsMbhRecipient->setEmail($bodySections['arMachine']['Original-rcpt-to']);
            $tmpRecipients[] = $cwsMbhRecipient;
        } elseif (!self::isEmpty($header,
                'Subject') && preg_match('#auto.{0,20}reply|vacation|(out|away|on holiday).*office#i',
                $header['Subject'])) {
            $this->cwsDebug->simple('<strong>Autoreply</strong> engaged', CwsDebug::VERBOSE_DEBUG);
            $cwsMbhRecipient = new Recipient();
            $cwsMbhRecipient->setBounceCat(self::CAT_AUTOREPLY);
            $tmpRecipients[] = $cwsMbhRecipient;
        } elseif (self::isRfc1892Report($header)) {
            $this->cwsDebug->simple('<strong>RFC 1892 report</strong> engaged', CwsDebug::VERBOSE_DEBUG);
            $arBodyMachine = $this->parseBodySectionMachine($bodySections['machine']);
            if (!self::isEmpty($arBodyMachine['perRecipient'])) {
                foreach ($arBodyMachine['perRecipient'] as $arRecipient) {
                    $cwsMbhRecipient = new Recipient();
                    $cwsMbhRecipient->setAction(isset($arRecipient['Action']) ? $arRecipient['Action'] : null);
                    $cwsMbhRecipient->setStatus(isset($arRecipient['Status']) ? $arRecipient['Status'] : null);
                    $cwsMbhRecipient->setEmail(self::findEmail($arRecipient));
                    $tmpRecipients[] = $cwsMbhRecipient;
                }
            }
        } elseif (!self::isEmpty($header, 'X-failed-recipients')) {
            $this->cwsDebug->simple('<strong>X-failed-recipients</strong> engaged', CwsDebug::VERBOSE_DEBUG);
            $arEmails = explode(',', $header['X-failed-recipients']);
            foreach ($arEmails as $email) {
                $cwsMbhRecipient = new Recipient();
                $cwsMbhRecipient->setEmail(trim($email));
                $tmpRecipients[] = $cwsMbhRecipient;
            }
        } elseif (isset($header['Content-type']) && !self::isEmpty($header['Content-type'],
                'boundary') && self::isBounce($header)) {
            $this->cwsDebug->simple('<strong>First body part</strong> engaged', CwsDebug::VERBOSE_DEBUG);
            $arEmails = self::findEmails($bodySections['first']);
            foreach ($arEmails as $email) {
                $cwsMbhRecipient = new Recipient();
                $cwsMbhRecipient->setEmail(trim($email));
                $tmpRecipients[] = $cwsMbhRecipient;
            }
        } elseif (self::isBounce($header)) {
            $this->cwsDebug->simple('<strong>Other bounces</strong> engaged', CwsDebug::VERBOSE_DEBUG);
            $arEmails = self::findEmails($body);
            foreach ($arEmails as $email) {
                $cwsMbhRecipient = new Recipient();
                $cwsMbhRecipient->setEmail(trim($email));
                $tmpRecipients[] = $cwsMbhRecipient;
            }
        } else {
            $cwsMbhMail->setProcessed(false);
        }

        // check subject
        if (!$cwsMbhMail->getSubject() && isset($header['Subject'])) {
            $cwsMbhMail->setSubject($header['Subject']);
        }

        if (count($tmpRecipients) > 0) {
            foreach ($tmpRecipients as $cwsMbhRecipient) {
                /* @var $cwsMbhRecipient Recipient */
                // check status
                if (!$cwsMbhRecipient->getStatus()) {
                    $cwsMbhRecipient->setStatus($this->findStatusCodeByRecipient($body));
                } else {
                    $cwsMbhRecipient->setStatus(self::formatStatusCode($cwsMbhRecipient->getStatus()));
                }

                // check action
                if (!$cwsMbhRecipient->getAction()) {
                    $cwsMbhRecipient->setAction($this->findActionByStatusCode($cwsMbhRecipient->getStatus()));
                }

                // check bounce cat / type
                if ($cwsMbhRecipient->getBounceCat() == self::CAT_UNRECOGNIZED && $cwsMbhRecipient->getStatus()) {
                    $ruleCat = self::getRuleCatByStatusCode($cwsMbhRecipient->getStatus());
                    if ($ruleCat != null) {
                        $cwsMbhRecipient->setBounceCat($ruleCat['name']);
                        $cwsMbhRecipient->setBounceType($ruleCat['bounceType']);
                        $cwsMbhRecipient->setRemove($ruleCat['remove']);
                    }
                }

                $cwsMbhMail->addRecipient($cwsMbhRecipient);
            }
        }

        $this->cwsDebug->dump('Result', $cwsMbhMail, CwsDebug::VERBOSE_REPORT);

        return $cwsMbhMail;
    }

    /**
     * Find an action by the status code.
     *
     * @param string $statusCode : the status code
     *
     * @return string
     */
    protected function findActionByStatusCode($statusCode)
    {
        $result = '';

        if (empty($statusCode)) {
            return $result;
        }

        $statusCode = self::formatStatusCode($statusCode);

        $arStatusCode = explode('.', $statusCode);
        switch ($arStatusCode[0]) {
            case '2':
                $result = 'success';
                break;
            case '4':
                $result = 'transient';
                break;
            case '5':
                $result = 'failed';
                break;
            default:
                return '';
                break;
        }

        if (!empty($result)) {
            $this->cwsDebug->simple('Action type <strong>' . $result . '</strong> found via status code',
                CwsDebug::VERBOSE_DEBUG);
        }

        return $result;
    }

    /**
     * Find an status code in body content.
     *
     * @param string $body : the body
     *
     * @return string
     */
    protected function findStatusCodeByRecipient($body)
    {
        $arBody = explode("\r\n", $body);

        foreach ($arBody as $bodyLine) {
            $bodyLine = trim($bodyLine);

            // From string
            $statusCode = self::getStatusCodeFromPattern($bodyLine);
            if (!empty($statusCode)) {
                $this->cwsDebug->simple('Status code <strong>' . $statusCode . '</strong> found via code resolver pattern',
                    CwsDebug::VERBOSE_DEBUG);

                return $statusCode;
            }

            // RFC 1893 (http://www.ietf.org/rfc/rfc1893.txt) return code
            if (preg_match('#\W([245]\.[01234567]\.[012345678])\W#', $bodyLine, $matches)) {
                if (stripos($bodyLine, 'Message-ID') !== false) {
                    break;
                }
                $statusCode = $matches[1];
                $statusCode = self::formatStatusCode($statusCode);
                $this->cwsDebug->simple('Status code <strong>' . $statusCode . '</strong> found via RFC 1893.',
                    CwsDebug::VERBOSE_DEBUG);

                return $statusCode;
            }

            // RFC 821 (http://www.ietf.org/rfc/rfc821.txt) return code
            if (preg_match('#\]?: ([45][01257][012345]) #', $bodyLine,
                    $matches) || preg_match('#^([45][01257][012345]) (?:.*?)(?:denied|inactive|deactivated|rejected|disabled|unknown|no such|not (?:our|activated|a valid))+#i',
                    $bodyLine, $matches)) {
                $statusCode = $matches[1];
                // map to new RFC
                if ($statusCode == '450' || $statusCode == '550' || $statusCode == '551' || $statusCode == '554') {
                    $statusCode = '511';
                } elseif ($statusCode == '452' || $statusCode == '552') {
                    $statusCode = '422';
                } elseif ($statusCode == '421') {
                    $statusCode = '432';
                }
                $statusCode = self::formatStatusCode($statusCode);
                $this->cwsDebug->simple('Status code <strong>' . $statusCode . '</strong> found and converted via RFC 821.',
                    CwsDebug::VERBOSE_DEBUG);

                return $statusCode;
            }
        }

        return null;
    }

    /**
     * Function to parse and process the body section machine.
     *
     * @param string $bodySectionMachine : the body section machine
     *
     * @return array
     */
    protected function parseBodySectionMachine($bodySectionMachine)
    {
        $result = self::parseDsnFields($bodySectionMachine);
        $result['mimeHeader'] = self::parseLines($result['mimeHeader']);
        $result['perMessage'] = self::parseLines($result['perMessage']);

        if (!self::isEmpty($result['perMessage'], 'X-postfix-sender')) {
            $arTmp = explode(';', $result['perMessage']['X-postfix-sender']);
            $result['perMessage']['X-postfix-sender'] = array(
                'type' => isset($arTmp[0]) ? trim($arTmp[0]) : null,
                'addr' => isset($arTmp[1]) ? trim($arTmp[1]) : null,
            );
        }
        if (!self::isEmpty($result['perMessage'], 'Reporting-mta')) {
            $arTmp = explode(';', $result['perMessage']['Reporting-mta']);
            $result['perMessage']['Reporting-mta'] = array(
                'type' => isset($arTmp[0]) ? trim($arTmp[0]) : null,
                'addr' => isset($arTmp[1]) ? trim($arTmp[1]) : null,
            );
        }

        $tmpPerRecipient = array();
        if (is_array($result['perRecipient'])) {
            foreach ($result['perRecipient'] as $perRecipient) {
                $arPerRecipient = self::parseLines(explode("\r\n", $perRecipient));
                $arPerRecipient['Final-recipient'] = isset($arPerRecipient['Final-recipient']) ? self::formatFinalRecipient($arPerRecipient['Final-recipient']) : null;
                $arPerRecipient['Original-recipient'] = isset($arPerRecipient['Original-recipient']) ? self::formatOriginalRecipient($arPerRecipient['Original-recipient']) : null;
                $arPerRecipient['Diagnostic-code'] = isset($arPerRecipient['Diagnostic-code']) ? self::formatDiagnosticCode($arPerRecipient['Diagnostic-code']) : null;

                // check if diagnostic code is a temporary failure
                if (isset($arPerRecipient['Diagnostic-code'])) {
                    $statusCode = self::formatStatusCode($arPerRecipient['Diagnostic-code']['text']);
                    $action = $this->findActionByStatusCode($statusCode);
                    if ($action == 'transient' && stristr($arPerRecipient['Action'], 'failed') !== false) {
                        $arPerRecipient['Action'] = 'transient';
                        $arPerRecipient['Status'] = '4.3.0';
                    }
                }

                $tmpPerRecipient[] = $arPerRecipient;
            }
        }

        $result['perRecipient'] = $tmpPerRecipient;

        return $result;
    }

    /**
     * Function to parse the header with some custom fields.
     *
     * @param string|array $arHeader : the array or plain text headers
     *
     * @return array
     */
    protected static function parseHeader($arHeader)
    {
        if (!is_array($arHeader)) {
            $arHeader = explode("\r\n", $arHeader);
        }
        $arHeader = self::parseLines($arHeader);

        if (isset($arHeader['Received'])) {
            $arrRec = explode('|', $arHeader['Received']);
            $arHeader['Received'] = $arrRec;
        }

        if (isset($arHeader['Content-type'])) {
            $ar_mr = explode(';', $arHeader['Content-type']);
            $arHeader['Content-type'] = array('type' => strtolower($ar_mr[0]));
            foreach ($ar_mr as $mr) {
                if (preg_match('#([^=.]*?)=(.*)#i', $mr, $matches)) {
                    $arHeader['Content-type'][strtolower(trim($matches[1]))] = str_replace('"', '', $matches[2]);
                }
            }
        }

        foreach ($arHeader as $key => $value) {
            if (strtolower($key) == 'x-hmxmroriginalrecipient') {
                unset($arHeader[$key]);
                $arHeader['X-HmXmrOriginalRecipient'] = $value;
            }
        }

        return $arHeader;
    }

    /**
     * Function to parse body by sections.
     *
     * @param array $arHeader : the array headers
     * @param string $body : the body content
     *
     * @return array
     */
    protected static function parseBodySections($arHeader, $body)
    {
        $sections = array();

        if (is_array($arHeader) && isset($arHeader['Content-type']) && isset($arHeader['Content-type']['boundary'])) {
            $arBoundary = explode($arHeader['Content-type']['boundary'], $body);
            $sections['first'] = isset($arBoundary[1]) ? $arBoundary[1] : null;
            $sections['arFirst'] = isset($arBoundary[1]) ? self::parseHeader($arBoundary[1]) : null;
            $sections['machine'] = isset($arBoundary[2]) ? $arBoundary[2] : null;
            $sections['returned'] = isset($arBoundary[3]) ? $arBoundary[3] : null;
        }

        return $sections;
    }

    /**
     * Function to parse DSN fields.
     *
     * @param string $bodySectionMachine : the body section machine
     *
     * @return array
     */
    protected static function parseDsnFields($bodySectionMachine)
    {
        $result = array(
            'mimeHeader' => null,
            'perMessage' => null,
            'perRecipient' => null,
        );

        $dsnFields = explode("\r\n\r\n", $bodySectionMachine);

        $recipientIndex = 0;
        for ($i = 0; $i < count($dsnFields); $i++) {
            $dsnFields[$i] = trim($dsnFields[$i]);
            if ($i == 0) {
                $result['mimeHeader'] = $dsnFields[$i];
            } elseif ($i == 1 && !preg_match('#(Final|Original)-Recipient#', $dsnFields[$i])) {
                // second element in the array should be a perRecipient
                $result['perMessage'] = $dsnFields[$i];
            } else {
                if ($dsnFields[$i] == '--') {
                    continue;
                }
                $result['perRecipient'][$recipientIndex] = $dsnFields[$i];
                $recipientIndex++;
            }
        }

        return $result;
    }

    /**
     * Function to parse standard fields.
     *
     * @param string|array $content : a generic content
     *
     * @return array
     */
    protected static function parseLines($content)
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
                } elseif (isset($result['Received']) && $result['Received']) {
                    if ($entity && $matches[2] && $matches[2] != $result[$entity]) {
                        $result[$entity] .= '|' . trim($matches[2]);
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
     *
     * @param array $arHeader : the array headers
     *
     * @return bool
     */
    protected static function isBounce($arHeader)
    {
        if (!empty($arHeader)) {
            $pregSubject = 'mail delivery failed|failure notice|warning: message|delivery status notif|delivery failure|delivery problem|';
            $pregSubject .= 'spam eater|returned mail|undeliverable|returned mail|delivery errors|mail status report|mail system error|';
            $pregSubject .= 'failure delivery|delivery notification|delivery has failed|undelivered mail|returned email|returning message to sender|';
            $pregSubject .= 'returned to sender|message delayed|mdaemon notification|mailserver notification|mail delivery system|nondeliverable mail|';
            $pregSubject .= 'mail transaction failed)|auto.{0,20}reply|vacation|(out|away|on holiday';

            if (isset($arHeader['Subject']) && preg_match('#(' . $pregSubject . ').*office#i', $arHeader['Subject'])) {
                return true;
            } elseif (isset($arHeader['Precedence']) && preg_match('#auto_reply#', $arHeader['Precedence'])) {
                return true;
            } elseif (isset($arHeader['From']) && preg_match('#auto_reply#', $arHeader['From'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Function to check if a message is a feedback loop via headers and body informations.
     *
     * @param array $arHeader : the array headers
     * @param array $bodySections : the array body sections
     *
     * @return bool
     */
    protected static function isFbl($arHeader, $bodySections = array())
    {
        if (!empty($arHeader)) {
            if (isset($arHeader['Content-type']) && isset($arHeader['Content-type']['report-type']) && preg_match('#feedback-report#',
                    $arHeader['Content-type']['report-type'])) {
                return true;
            } elseif (isset($arHeader['X-loop']) && preg_match('#scomp#', $arHeader['X-loop'])) {
                return true;
            } elseif (self::isHotmailFbl($bodySections)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Function to check if a message is a hotmail feedback loop via body informations.
     *
     * @param array $bodySections : the array body sections
     *
     * @return bool
     */
    protected static function isHotmailFbl($bodySections)
    {
        return !empty($bodySections) && isset($bodySections['arFirst']) && isset($bodySections['arFirst']['X-HmXmrOriginalRecipient']);
    }

    /**
     * Function to check if a message is a specific RFC 1892 report via headers informations.
     *
     * @param array $arHeader : the array headers
     *
     * @return bool
     */
    protected static function isRfc1892Report($arHeader)
    {
        if (empty($arHeader)) {
            return false;
        }

        // type
        if (self::isEmpty($arHeader, 'Content-type')
            || self::isEmpty($arHeader['Content-type'], 'type')
            || $arHeader['Content-type']['type'] != 'multipart/report') {
            return false;
        }

        // report-type
        if (self::isEmpty($arHeader['Content-type'], 'report-type')
            || $arHeader['Content-type']['report-type'] != 'delivery-status') {
            return false;
        }

        // boundary
        if (self::isEmpty($arHeader['Content-type'], 'boundary') || $arHeader['Content-type']['boundary'] === '') {
            return false;
        }

        return true;
    }

    /**
     * Format the final recipient with e-mail and type.
     *
     * @param string $finalRecipient : the final recipient
     *
     * @return array
     */
    protected static function formatFinalRecipient($finalRecipient)
    {
        $result = array(
            'addr' => '',
            'type' => '',
        );

        $arFinalRecipient = explode(';', $finalRecipient);
        if (empty($arFinalRecipient)) {
            return $result;
        }

        if (strpos($arFinalRecipient[0], '@') !== false) {
            $result['addr'] = self::extractEmail($arFinalRecipient[0]);
            $result['type'] = !self::isEmpty($arFinalRecipient, 1) ? trim($arFinalRecipient[1]) : 'unknown';
        } else {
            $result['addr'] = self::extractEmail($arFinalRecipient[1]);
            $result['type'] = !self::isEmpty($arFinalRecipient, 0) ? trim($arFinalRecipient[0]) : '';
        }

        return $result;
    }

    /**
     * Format the original recipient with e-mail and type.
     *
     * @param string $originalRecipient : the original recipient
     *
     * @return array
     */
    protected static function formatOriginalRecipient($originalRecipient)
    {
        $result = array(
            'addr' => '',
            'type' => '',
        );

        $arOriginalRecipient = explode(';', $originalRecipient);
        if (empty($arOriginalRecipient)) {
            return $result;
        }

        $result['addr'] = self::extractEmail($arOriginalRecipient[1]);
        $result['type'] = !self::isEmpty($arOriginalRecipient, 0) ? trim($arOriginalRecipient[0]) : '';

        return $result;
    }

    /**
     * Format the diagnostic code with type and text.
     *
     * @param string $diagCode : the diagnostic recipient
     *
     * @return array
     */
    protected static function formatDiagnosticCode($diagCode)
    {
        $result = array(
            'type' => '',
            'text' => '',
        );

        $arDiagCode = explode(';', $diagCode);
        if (empty($arDiagCode)) {
            return $result;
        }

        $result['type'] = !self::isEmpty($arDiagCode, 0) ? trim($arDiagCode[0]) : '';
        $result['text'] = !self::isEmpty($arDiagCode, 1) ? trim($arDiagCode[1]) : '';

        return $result;
    }



    /**
     * Get explanations from DSN status code via the RFC 1893 : http://www.ietf.org/rfc/rfc1893.txt<br />
     * This method returns an array with the following values :<br />
     * * string STATUS_CODE the status code.
     * * array STATUS_FIRST_SUBCODE array containing title and description of the first subcode.
     * * array STATUS_SECOND_SUBCODE array containing title and description of the second subcode.
     * * array STATUS_THIRD_SUBCODE array containing title and description of the third subcode.
     *
     * @param string $statusCode : consist of three numerical fields separated by ".".
     *
     * @return array
     */
    public static function getStatusCodeExplanations($statusCode)
    {
        $result = array(
            self::STATUS_CODE => null,
            self::STATUS_FIRST_SUBCODE => array(),
            self::STATUS_SECOND_SUBCODE => array(),
            self::STATUS_THIRD_SUBCODE => array(),
        );

        $statusCode = self::formatStatusCode($statusCode);
        if (self::isEmpty($statusCode)) {
            return $result;
        }

        $arStatusCode = explode('.', $statusCode);
        if ($arStatusCode == null || count($arStatusCode) != 3) {
            return $result;
        }

        $result[self::STATUS_CODE] = $statusCode;

        // First sub-code : indicates whether the delivery attempt was successful
        switch ($arStatusCode[0]) {
            case '2':
                $result[self::STATUS_FIRST_SUBCODE] = array(
                    'title' => 'Success',
                    'desc' => 'Success specifies that the DSN is reporting a positive delivery action. Detail sub-codes may provide notification of transformations required for delivery.',
                );
                break;

            case '4':
                $result[self::STATUS_FIRST_SUBCODE] = array(
                    'title' => 'Persistent Transient Failure',
                    'desc' => 'A persistent transient failure is one in which the message as sent is valid, but some temporary event prevents the successful sending of the message. Sending in the future may be successful.',
                );
                break;

            case '5':
                $result[self::STATUS_FIRST_SUBCODE] = array(
                    'title' => 'Permanent Failure',
                    'desc' => 'A permanent failure is one which is not likely to be resolved by resending the message in the current form. Some change to the message or the destination must be made for successful delivery.',
                );
                break;

            default:
                break;
        }

        // Second sub-code : indicates the probable source of any delivery anomalies
        switch ($arStatusCode[1]) {
            case '0':
                $result[self::STATUS_SECOND_SUBCODE] = array(
                    'title' => 'Other or Undefined Status',
                    'desc' => 'There is no additional subject information available.',
                );
                break;

            case '1':
                $result[self::STATUS_SECOND_SUBCODE] = array(
                    'title' => 'Addressing Status',
                    'desc' => 'The address status reports on the originator or destination address. It may include address syntax or validity. These errors can generally be corrected by the sender and retried.',
                );
                break;

            case '2':
                $result[self::STATUS_SECOND_SUBCODE] = array(
                    'title' => 'Mailbox Status',
                    'desc' => 'Mailbox status indicates that something having to do with the mailbox has cause this DSN. Mailbox issues are assumed to be under the general control of the recipient.',
                );
                break;

            case '3':
                $result[self::STATUS_SECOND_SUBCODE] = array(
                    'title' => 'Mail System Status',
                    'desc' => 'Mail system status indicates that something having to do with the destination system has caused this DSN. System issues are assumed to be under the general control of the destination system administrator.',
                );
                break;

            case '4':
                $result[self::STATUS_SECOND_SUBCODE] = array(
                    'title' => 'Network and Routing Status',
                    'desc' => 'The networking or routing codes report status about the delivery system itself. These system components include any necessary infrastructure such as directory and routing services. Network issues are assumed to be under the control of the destination or intermediate system administrator.',
                );
                break;

            case '5':
                $result[self::STATUS_SECOND_SUBCODE] = array(
                    'title' => 'Mail Delivery Protocol Status',
                    'desc' => 'The mail delivery protocol status codes report failures involving the message delivery protocol. These failures include the full range of problems resulting from implementation errors or an unreliable connection. Mail delivery protocol issues may be controlled by many parties including the originating system, destination system, or intermediate system administrators.',
                );
                break;

            case '6':
                $result[self::STATUS_SECOND_SUBCODE] = array(
                    'title' => 'Message Content or Media Status',
                    'desc' => 'The message content or media status codes report failures involving the content of the message. These codes report failures due to translation, transcoding, or otherwise unsupported message media. Message content or media issues are under the control of both the sender and the receiver, both of whom must support a common set of supported content-types.',
                );
                break;

            case '7':
                $result[self::STATUS_SECOND_SUBCODE] = array(
                    'title' => 'Security or Policy Status',
                    'desc' => 'The security or policy status codes report failures involving policies such as per-recipient or per-host filtering and cryptographic operations. Security and policy status issues are assumed to be under the control of either or both the sender and recipient. Both the sender and recipient must permit the exchange of messages and arrange the exchange of necessary keys and certificates for cryptographic operations.',
                );
                break;

            default:
                break;
        }

        // Second and Third sub-code : indicates a precise error condition
        switch ($arStatusCode[1] . '.' . $arStatusCode[2]) {
            case '0.0':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Other undefined Status',
                    'desc' => 'Other undefined status is the only undefined error code. It should be used for all errors for which only the class of the error is known.',
                );
                break;

            case '1.0':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Other address status',
                    'desc' => 'Something about the address specified in the message caused this DSN.',
                );
                break;

            case '1.1':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Bad destination mailbox address',
                    'desc' => 'The mailbox specified in the address does not exist. For Internet mail names, this means the address portion to the left of the @ sign is invalid. This code is only useful for permanent failures.',
                );
                break;

            case '1.2':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Bad destination system address',
                    'desc' => 'The destination system specified in the address does not exist or is incapable of accepting mail. For Internet mail names, this means the address portion to the right of the @ is invalid for mail. This codes is only useful for permanent failures.',
                );
                break;

            case '1.3':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Bad destination mailbox address syntax',
                    'desc' => 'The destination address was syntactically invalid. This can apply to any field in the address. This code is only useful for permanent failures.',
                );
                break;

            case '1.4':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Destination mailbox address ambiguous',
                    'desc' => 'The mailbox address as specified matches one or more recipients on the destination system. This may result if a heuristic address mapping algorithm is used to map the specified address to a local mailbox name.',
                );
                break;

            case '1.5':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Destination address valid',
                    'desc' => 'This mailbox address as specified was valid. This status code should be used for positive delivery reports.',
                );
                break;

            case '1.6':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Destination mailbox has moved, No forwarding address',
                    'desc' => 'The mailbox address provided was at one time valid, but mail is no longer being accepted for that address. This code is only useful for permanent failures.',
                );
                break;

            case '1.7':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Bad sender\'s mailbox address syntax',
                    'desc' => 'The sender\'s address was syntactically invalid. This can apply to any field in the address.',
                );
                break;

            case '1.8':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Bad sender\'s system address',
                    'desc' => 'The sender\'s system specified in the address does not exist or is incapable of accepting return mail. For domain names, this means the address portion to the right of the @ is invalid for mail.',
                );
                break;

            case '2.0':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Other or undefined mailbox status',
                    'desc' => 'The mailbox exists, but something about the destination mailbox has caused the sending of this DSN.',
                );
                break;

            case '2.1':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Mailbox disabled, not accepting messages',
                    'desc' => 'The mailbox exists, but is not accepting messages. This may be a permanent error if the mailbox will never be re-enabled or a transient error if the mailbox is only temporarily disabled.',
                );
                break;

            case '2.2':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Mailbox full',
                    'desc' => 'The mailbox is full because the user has exceeded a per-mailbox administrative quota or physical capacity. The general semantics implies that the recipient can delete messages to make more space available. This code should be used as a persistent transient failure.',
                );
                break;

            case '2.3':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Message length exceeds administrative limit',
                    'desc' => 'A per-mailbox administrative message length limit has been exceeded. This status code should be used when the per-mailbox message length limit is less than the general system limit. This code should be used as a permanent failure.',
                );
                break;

            case '2.4':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Mailing list expansion problem',
                    'desc' => 'The mailbox is a mailing list address and the mailing list was unable to be expanded. This code may represent a permanent failure or a persistent transient failure.',
                );
                break;

            case '3.0':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Other or undefined mail system status',
                    'desc' => 'The destination system exists and normally accepts mail, but something about the system has caused the generation of this DSN.',
                );
                break;

            case '3.1':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Mail system full',
                    'desc' => 'Mail system storage has been exceeded. The general semantics imply that the individual recipient may not be able to delete material to make room for additional messages. This is useful only as a persistent transient error.',
                );
                break;

            case '3.2':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'System not accepting network messages',
                    'desc' => 'The host on which the mailbox is resident is not accepting messages. Examples of such conditions include an immanent shutdown, excessive load, or system maintenance. This is useful for both permanent and permanent transient errors.',
                );
                break;

            case '3.3':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'System not capable of selected features',
                    'desc' => 'Selected features specified for the message are not supported by the destination system. This can occur in gateways when features from one domain cannot be mapped onto the supported feature in another.',
                );
                break;

            case '3.4':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Message too big for system',
                    'desc' => 'The message is larger than per-message size limit. This limit may either be for physical or administrative reasons. This is useful only as a permanent error.',
                );
                break;

            case '3.5':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'System incorrectly configured',
                    'desc' => 'The system is not configured in a manner which will permit it to accept this message.',
                );
                break;

            case '4.0':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Other or undefined network or routing status',
                    'desc' => 'Something went wrong with the networking, but it is not clear what the problem is, or the problem cannot be well expressed with any of the other provided detail codes.',
                );
                break;

            case '4.1':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'No answer from host',
                    'desc' => 'The outbound connection attempt was not answered, either because the remote system was busy, or otherwise unable to take a call. This is useful only as a persistent transient error.',
                );
                break;

            case '4.2':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Bad connection',
                    'desc' => 'The outbound connection was established, but was otherwise unable to complete the message transaction, either because of time-out, or inadequate connection quality. This is useful only as a persistent transient error.',
                );
                break;

            case '4.3':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Directory server failure',
                    'desc' => 'The network system was unable to forward the message, because a directory server was unavailable. This is useful only as a persistent transient error. The inability to connect to an Internet DNS server is one example of the directory server failure error.',
                );
                break;

            case '4.4':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Unable to route',
                    'desc' => 'The mail system was unable to determine the next hop for the message because the necessary routing information was unavailable from the directory server. This is useful for both permanent and persistent transient errors. A DNS lookup returning only an SOA (Start of Administration) record for a domain name is one example of the unable to route error.',
                );
                break;

            case '4.5':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Mail system congestion',
                    'desc' => 'The mail system was unable to deliver the message because the mail system was congested. This is useful only as a persistent transient error.',
                );
                break;

            case '4.6':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Routing loop detected',
                    'desc' => 'A routing loop caused the message to be forwarded too many times, either because of incorrect routing tables or a user forwarding loop. This is useful only as a persistent transient error.',
                );
                break;

            case '4.7':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Delivery time expired',
                    'desc' => 'The message was considered too old by the rejecting system, either because it remained on that host too long or because the time-to-live value specified by the sender of the message was exceeded. If possible, the code for the actual problem found when delivery was attempted should be returned rather than this code. This is useful only as a persistent transient error.',
                );
                break;

            case '5.0':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Other or undefined protocol status',
                    'desc' => 'Something was wrong with the protocol necessary to deliver the message to the next hop and the problem cannot be well expressed with any of the other provided detail codes.',
                );
                break;

            case '5.1':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Invalid command',
                    'desc' => 'A mail transaction protocol command was issued which was either out of sequence or unsupported. This is useful only as a permanent error.',
                );
                break;

            case '5.2':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Syntax error',
                    'desc' => 'A mail transaction protocol command was issued which could not be interpreted, either because the syntax was wrong or the command is unrecognized. This is useful only as a permanent error.',
                );
                break;

            case '5.3':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Too many recipients',
                    'desc' => 'More recipients were specified for the message than could have been delivered by the protocol. This error should normally result in the segmentation of the message into two, the remainder of the recipients to be delivered on a subsequent delivery attempt. It is included in this list in the event that such segmentation is not possible.',
                );
                break;

            case '5.4':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Invalid command arguments',
                    'desc' => 'A valid mail transaction protocol command was issued with invalid arguments, either because the arguments were out of range or represented unrecognized features. This is useful only as a permanent error.',
                );
                break;

            case '5.5':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Wrong protocol version',
                    'desc' => 'A protocol version mis-match existed which could not be automatically resolved by the communicating parties.',
                );
                break;

            case '6.0':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Other or undefined media error',
                    'desc' => 'Something about the content of a message caused it to be considered undeliverable and the problem cannot be well expressed with any of the other provided detail codes.',
                );
                break;

            case '6.1':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Media not supported',
                    'desc' => 'The media of the message is not supported by either the delivery protocol or the next system in the forwarding path. This is useful only as a permanent error.',
                );
                break;

            case '6.2':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Conversion required and prohibited',
                    'desc' => 'The content of the message must be converted before it can be delivered and such conversion is not permitted. Such prohibitions may be the expression of the sender in the message itself or the policy of the sending host.',
                );
                break;

            case '6.3':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Conversion required but not supported',
                    'desc' => 'The message content must be converted to be forwarded but such conversion is not possible or is not practical by a host in the forwarding path. This condition may result when an ESMTP gateway supports 8bit transport but is not able to downgrade the message to 7 bit as required for the next hop.',
                );
                break;

            case '6.4':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Conversion with loss performed',
                    'desc' => 'This is a warning sent to the sender when message delivery was successfully but when the delivery required a conversion in which some data was lost. This may also be a permanant error if the sender has indicated that conversion with loss is prohibited for the message.',
                );
                break;

            case '6.5':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Conversion Failed',
                    'desc' => 'A conversion was required but was unsuccessful. This may be useful as a permanent or persistent temporary notification.',
                );
                break;

            case '7.0':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Other or undefined security status',
                    'desc' => 'Something related to security caused the message to be returned, and the problem cannot be well expressed with any of the other provided detail codes. This status code may also be used when the condition cannot be further described because of security policies in force.',
                );
                break;

            case '7.1':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Delivery not authorized, message refused',
                    'desc' => 'The sender is not authorized to send to the destination. This can be the result of per-host or per-recipient filtering. This memo does not discuss the merits of any such filtering, but provides a mechanism to report such. This is useful only as a permanent error.',
                );
                break;

            case '7.2':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Mailing list expansion prohibited',
                    'desc' => 'The sender is not authorized to send a message to the intended mailing list. This is useful only as a permanent error.',
                );
                break;

            case '7.3':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Security conversion required but not possible',
                    'desc' => 'A conversion from one secure messaging protocol to another was required for delivery and such conversion was not possible. This is useful only as a permanent error.',
                );
                break;

            case '7.4':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Security features not supported',
                    'desc' => 'A message contained security features such as secure authentication which could not be supported on the delivery protocol. This is useful only as a permanent error.',
                );
                break;

            case '7.5':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Cryptographic failure',
                    'desc' => 'A transport system otherwise authorized to validate or decrypt a message in transport was unable to do so because necessary information such as key was not available or such information was invalid.',
                );
                break;

            case '7.6':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Cryptographic algorithm not supported',
                    'desc' => 'A transport system otherwise authorized to validate or decrypt a message was unable to do so because the necessary algorithm was not supported.',
                );
                break;

            case '7.7':
                $result[self::STATUS_THIRD_SUBCODE] = array(
                    'title' => 'Message integrity failure',
                    'desc' => 'A transport system otherwise authorized to validate a message was unable to do so because the message was corrupted or altered. This may be useful as a permanent, transient persistent, or successful delivery code.',
                );
                break;

            default:
                break;
        }

        return $result;
    }

    /**
     * Find status code from string.
     *
     * @param string
     *
     * @return string
     */
    protected static function getStatusCodeFromPattern($pattern)
    {
        $statusCodeResolver = array(
            // regexp
            '[45]\d\d[- ]\#?([45]\.\d\.\d)' => 'x',
            'Diagnostic[- ][Cc]ode: smtp; ?\d\d\ ([45]\.\d\.\d)' => 'x',
            'Status: ([45]\.\d\.\d)' => 'x',
            // 4.2.0
            'not yet been delivered' => '4.2.0',
            'message will be retried for' => '4.2.0',
            // 4.2.2
            'benutzer hat zuviele mails auf dem server' => '4.2.2',
            'exceeded storage allocation' => '4.2.2',
            'mailbox full' => '4.2.2',
            'mailbox is full' => '4.2.2',
            'mailbox quota usage exceeded' => '4.2.2',
            'mailbox size limit exceeded' => '4.2.2',
            'mailfolder is full' => '4.2.2',
            'not enough storage space' => '4.2.2',
            'over ?quota' => '4.2.2',
            'quota exceeded' => '4.2.2',
            'quota violation' => '4.2.2',
            'user has exhausted allowed storage space' => '4.2.2',
            'user has too many messages on the server' => '4.2.2',
            'user mailbox exceeds allowed size' => '4.2.2',
            'user has Exceeded' => '4.2.2',
            // 4.3.2
            'delivery attempts will continue to be made for' => '4.3.2',
            'delivery temporarily suspended' => '4.3.2',
            'greylisted for 5 minutes' => '4.3.2',
            'greylisting in action' => '4.3.2',
            'server busy' => '4.3.2',
            'server too busy' => '4.3.2',
            'system load is too high' => '4.3.2',
            'temporarily deferred' => '4.3.2',
            'temporarily unavailable' => '4.3.2',
            'throttling' => '4.3.2',
            'too busy to accept mail' => '4.3.2',
            'too many connections' => '4.3.2',
            'too many sessions' => '4.3.2',
            'too much load' => '4.3.2',
            'try again later' => '4.3.2',
            'try later' => '4.3.2',
            // 4.4.7
            'retry timeout exceeded' => '4.4.7',
            'queue too long' => '4.4.7',
            // 5.1.1
            '554 delivery error:' => '5.1.1',
            'account has been disabled' => '5.1.1',
            'account is unavailable' => '5.1.1',
            'account not found' => '5.1.1',
            'address invalid' => '5.1.1',
            'address is unknown' => '5.1.1',
            'address unknown' => '5.1.1',
            'addressee unknown' => '5.1.1',
            'address_not_found' => '5.1.1',
            'bad address' => '5.1.1',
            'bad destination mailbox address' => '5.1.1',
            'destin. Sconosciuto' => '5.1.1',
            'destinatario errato' => '5.1.1',
            'destinatario sconosciuto o mailbox disatttivata' => '5.1.1',
            'does not exist' => '5.1.1',
            'email Address was not found' => '5.1.1',
            'excessive userid unknowns' => '5.1.1',
            'Indirizzo inesistente' => '5.1.1',
            'Invalid account' => '5.1.1',
            'invalid address' => '5.1.1',
            'invalid or unknown virtual user' => '5.1.1',
            'invalid mailbox' => '5.1.1',
            'invalid recipient' => '5.1.1',
            'mailbox not found' => '5.1.1',
            'mailbox unavailable' => '5.1.1',
            'nie istnieje' => '5.1.1',
            'nie ma takiego konta' => '5.1.1',
            'no mail box available for this user' => '5.1.1',
            'no mailbox here' => '5.1.1',
            'no one with that email address here' => '5.1.1',
            'no such address' => '5.1.1',
            'no such email address' => '5.1.1',
            'no such mail drop defined' => '5.1.1',
            'no such mailbox' => '5.1.1',
            'no such person at this address' => '5.1.1',
            'no such recipient' => '5.1.1',
            'no such user' => '5.1.1',
            'not a known user' => '5.1.1',
            'not a valid mailbox' => '5.1.1',
            'not a valid user' => '5.1.1',
            'not available' => '5.1.1',
            'not exists' => '5.1.1',
            'recipient address rejected' => '5.1.1',
            'recipient not allowed' => '5.1.1',
            'recipient not found' => '5.1.1',
            'recipient rejected' => '5.1.1',
            'recipient unknown' => '5.1.1',
            'server doesn\'t handle mail for that user' => '5.1.1',
            'this account is disabled' => '5.1.1',
            'this address no longer accepts mail' => '5.1.1',
            'this email address is not known to this system' => '5.1.1',
            'unknown account' => '5.1.1',
            'unknown address or alias' => '5.1.1',
            'unknown email address' => '5.1.1',
            'unknown local part' => '5.1.1',
            'unknown or illegal alias' => '5.1.1',
            'unknown or illegal user' => '5.1.1',
            'unknown recipient' => '5.1.1',
            'unknown user' => '5.1.1',
            'user disabled' => '5.1.1',
            'user doesn\'t exist in this server' => '5.1.1',
            'user invalid' => '5.1.1',
            'user is suspended' => '5.1.1',
            'user is unknown' => '5.1.1',
            'user not found' => '5.1.1',
            'user not known' => '5.1.1',
            'user unknown' => '5.1.1',
            'valid RCPT command must precede data' => '5.1.1',
            'was not found in ldap server' => '5.1.1',
            'we are sorry but the address is invalid' => '5.1.1',
            'unable to find alias user' => '5.1.1',
            'user doesn\'t have a yahoo\.[a-zA-Z]{2,3} account' => '5.1.1',
            // 5.1.2
            'domain isn\'t in my list of allowed rcpthosts' => '5.1.2',
            'esta casilla ha expirado por falta de uso' => '5.1.2',
            'host ?name is unknown' => '5.1.2',
            'no relaying allowed' => '5.1.2',
            'no such domain' => '5.1.2',
            'not our customer' => '5.1.2',
            'relay not permitted' => '5.1.2',
            'relay access denied' => '5.1.2',
            'relaying denied' => '5.1.2',
            'relaying not allowed' => '5.1.2',
            'this system is not configured to relay mail' => '5.1.2',
            'unable to relay' => '5.1.2',
            'unrouteable mail domain' => '5.1.2',
            'we do not relay' => '5.1.2',
            // 5.1.6
            'old address no longer valid' => '5.1.6',
            'recipient no longer on server' => '5.1.6',
            // 5.1.8
            'dender address rejected' => '5.1.8',
            // 5.2.0
            'delivery failed' => '5.2.0',
            'exceeded the rate limit' => '5.2.0',
            'local Policy Violation' => '5.2.0',
            'mailbox currently suspended' => '5.2.0',
            'mail can not be delivered' => '5.2.0',
            'mail couldn\'t be delivered' => '5.2.0',
            'the account or domain may not exist' => '5.2.0',
            // 5.2.1
            'account disabled' => '5.2.1',
            'account inactive' => '5.2.1',
            'inactive account' => '5.2.1',
            'adressat unbekannt oder mailbox deaktiviert' => '5.2.1',
            'destinataire inconnu ou boite aux lettres desactivee' => '5.2.1',
            'mail is not currently being accepted for this mailbox' => '5.2.1',
            'el usuario esta en estado: inactivo' => '5.2.1',
            'email account that you tried to reach is disabled' => '5.2.1',
            'inactive user' => '5.2.1',
            'user is inactive' => '5.2.1',
            'mailbox disabled for this recipient' => '5.2.1',
            'mailbox has been blocked due to inactivity' => '5.2.1',
            'mailbox is currently unavailable' => '5.2.1',
            'mailbox is disabled' => '5.2.1',
            'mailbox is inactive' => '5.2.1',
            'mailbox locked or suspended' => '5.2.1',
            'mailbox temporarily disabled' => '5.2.1',
            'podane konto jest zablokowane administracyjnie lub nieaktywne' => '5.2.1',
            'questo indirizzo e\' bloccato per inutilizzo' => '5.2.1',
            'recipient mailbox was disabled' => '5.2.1',
            'domain name not found' => '5.2.1',
            // 5.4.4
            'couldn\'t find any host named' => '5.4.4',
            'couldn\'t find any host by that name' => '5.4.4',
            'perm_failure: dns error' => '5.4.4',
            'temporary lookup failure' => '5.4.4',
            'unrouteable address' => '5.4.4',
            'can\'t connect to' => '5.4.4',
            // 5.4.6
            'too many hops' => '5.4.6',
            // 5.5.0
            'content reject' => '5.5.0',
            'requested action aborted' => '5.5.0',
            // 5.5.2
            'mime/reject' => '5.5.2',
            // 5.5.3
            'mail data refused' => '5.5.3',
            // 5.5.4
            'mime error' => '5.5.4',
            // 5.6.2
            'rejecting password protected file attachment' => '5.6.2',
            // 5.7.1
            '550 OU-00' => '5.7.1',
            '550 SC-00' => '5.7.1',
            '550 DY-00' => '5.7.1',
            '554 denied' => '5.7.1',
            'you have been blocked by the recipient' => '5.7.1',
            'requires that you verify' => '5.7.1',
            'access denied' => '5.7.1',
            'administrative prohibition - unable to validate recipient' => '5.7.1',
            'blacklisted' => '5.7.1',
            'blocke?d? for spam' => '5.7.1',
            'conection refused' => '5.7.1',
            'connection refused due to abuse' => '5.7.1',
            'dial-up or dynamic-ip denied' => '5.7.1',
            'domain has received too many bounces' => '5.7.1',
            'failed several antispam checks' => '5.7.1',
            'found in a dns blacklist' => '5.7.1',
            'ips blocked' => '5.7.1',
            'is blocked by' => '5.7.1',
            'mail Refused' => '5.7.1',
            'message does not pass domainkeys' => '5.7.1',
            'message looks like spam' => '5.7.1',
            'message refused by' => '5.7.1',
            'not allowed access from your location' => '5.7.1',
            'permanently deferred' => '5.7.1',
            'rejected by policy' => '5.7.1',
            'rejected by windows live hotmail for policy reasons' => '5.7.1',
            'rejected for policy reasons' => '5.7.1',
            'rejecting banned content' => '5.7.1',
            'sorry, looks like spam' => '5.7.1',
            'spam message discarded' => '5.7.1',
            'too many spams from your ip' => '5.7.1',
            'transaction failed' => '5.7.1',
            'transaction rejected' => '5.7.1',
            'wiadomosc zostala odrzucona przez system antyspamowy' => '5.7.1',
            'your message was declared spam' => '5.7.1',
        );

        foreach ($statusCodeResolver as $bounceBody => $bounceCode) {
            if (preg_match('#' . $bounceBody . '#is', $pattern, $matches)) {
                $statusCode = isset($matches[1]) ? $matches[1] : $bounceCode;
                $statusCode = self::formatStatusCode($statusCode);
                return $statusCode;
            }
        }

        return null;
    }

    /**
     * Format status code from regexp.
     *
     * @param string
     *
     * @return string
     */
    protected static function formatStatusCode($statusCode)
    {
        if (empty($statusCode)) {
            return null;
        }

        if (preg_match('#(\d\d\d)\s#', $statusCode, $match)) {
            $statusCode = $match[1];
        } elseif (preg_match('#(\d\.\d\.\d)\s#', $statusCode, $match)) {
            $statusCode = $match[1];
        }
        if (preg_match('#([245]\.[01234567]\.[012345678])(.*)#', $statusCode, $match)) {
            return $match[1];
        } elseif (preg_match('#([245][01234567][012345678])(.*)#', $statusCode, $match)) {
            preg_match_all('#.#', $match[1], $arStatusCode);
            if (is_array($arStatusCode[0]) && count($arStatusCode[0]) == 3) {
                return implode('.', $arStatusCode[0]);
            }
        }

        return null;
    }

    /**
     * Rule category
     * This var returns an array with the following values :
     * * string 'remove' is removed.
     * * string 'bounceType' type of bounce (see BOUNCE_ const).
     *
     * @param string
     *
     * @return array
     */
    protected static function getRuleCat($ruleCatName)
    {
        $ruleCatsData = array(
            array(
                'name' => self::CAT_ANTISPAM,
                'remove' => false,
                'bounceType' => self::BOUNCE_BLOCKED,
            ),
            array(
                'name' => self::CAT_AUTOREPLY,
                'remove' => false,
                'bounceType' => self::BOUNCE_AUTOREPLY,
            ),
            array(
                'name' => self::CAT_CONCURRENT,
                'remove' => false,
                'bounceType' => self::BOUNCE_SOFT,
            ),
            array(
                'name' => self::CAT_CONTENT_REJECT,
                'remove' => false,
                'bounceType' => self::BOUNCE_SOFT,
            ),
            array(
                'name' => self::CAT_COMMAND_REJECT,
                'remove' => true,
                'bounceType' => self::BOUNCE_HARD,
            ),
            array(
                'name' => self::CAT_DEFER,
                'remove' => false,
                'bounceType' => self::BOUNCE_SOFT,
            ),
            array(
                'name' => self::CAT_DELAYED,
                'remove' => false,
                'bounceType' => self::BOUNCE_TEMPORARY,
            ),
            array(
                'name' => self::CAT_DNS_LOOP,
                'remove' => true,
                'bounceType' => self::BOUNCE_HARD,
            ),
            array(
                'name' => self::CAT_DNS_UNKNOWN,
                'remove' => true,
                'bounceType' => self::BOUNCE_HARD,
            ),
            array(
                'name' => self::CAT_FULL,
                'remove' => false,
                'bounceType' => self::BOUNCE_SOFT,
            ),
            array(
                'name' => self::CAT_INACTIVE,
                'remove' => true,
                'bounceType' => self::BOUNCE_HARD,
            ),
            array(
                'name' => self::CAT_INTERNAL_ERROR,
                'remove' => false,
                'bounceType' => self::BOUNCE_TEMPORARY,
            ),
            array(
                'name' => self::CAT_LATIN_ONLY,
                'remove' => false,
                'bounceType' => self::BOUNCE_SOFT,
            ),
            array(
                'name' => self::CAT_OTHER,
                'remove' => true,
                'bounceType' => self::BOUNCE_GENERIC,
            ),
            array(
                'name' => self::CAT_OVERSIZE,
                'remove' => false,
                'bounceType' => self::BOUNCE_SOFT,
            ),
            array(
                'name' => self::CAT_TIMEOUT,
                'remove' => false,
                'bounceType' => self::BOUNCE_SOFT,
            ),
            array(
                'name' => self::CAT_UNKNOWN,
                'remove' => true,
                'bounceType' => self::BOUNCE_HARD,
            ),
            array(
                'name' => self::CAT_UNRECOGNIZED,
                'remove' => false,
                'bounceType' => null,
            ),
            array(
                'name' => self::CAT_USER_REJECT,
                'remove' => true,
                'bounceType' => self::BOUNCE_HARD,
            ),
            array(
                'name' => self::CAT_WARNING,
                'remove' => false,
                'bounceType' => self::BOUNCE_SOFT,
            ),
        );

        foreach ($ruleCatsData as $ruleCatData) {
            if ($ruleCatData['name'] == $ruleCatName) {
                return $ruleCatData;
            }
        }

        return null;
    }

    /**
     * Find rule cat from status code.
     *
     * @param string
     *
     * @return null|array ruleCat
     */
    protected static function getRuleCatByStatusCode($statusCode)
    {
        $ruleCatStatusCode = array(
            '4.0.0' => self::CAT_INTERNAL_ERROR,
            '4.2.0' => self::CAT_DEFER,
            '4.2.2' => self::CAT_FULL,
            '4.3.2' => self::CAT_DEFER,
            '4.4.7' => self::CAT_TIMEOUT,
            '4.5.1' => self::CAT_COMMAND_REJECT,
            '5.0.0' => self::CAT_UNKNOWN,
            '5.1.1' => self::CAT_UNKNOWN,
            '5.1.2' => self::CAT_UNKNOWN,
            '5.1.3' => self::CAT_UNKNOWN,
            '5.1.4' => self::CAT_UNKNOWN,
            '5.1.6' => self::CAT_UNKNOWN,
            '5.1.8' => self::CAT_ANTISPAM,
            '5.2.0' => self::CAT_FULL,
            '5.2.1' => self::CAT_USER_REJECT,
            '5.2.2' => self::CAT_FULL,
            '5.2.3' => self::CAT_UNKNOWN,
            '5.3.1' => self::CAT_OVERSIZE,
            '5.3.4' => self::CAT_OVERSIZE,
            '5.4.4' => self::CAT_UNKNOWN,
            '5.4.6' => self::CAT_ANTISPAM,
            '5.5.0' => self::CAT_CONTENT_REJECT,
            '5.5.2' => self::CAT_CONTENT_REJECT,
            '5.5.3' => self::CAT_CONTENT_REJECT,
            '5.5.4' => self::CAT_CONTENT_REJECT,
            '5.6.2' => self::CAT_CONTENT_REJECT,
            '5.7.0' => self::CAT_USER_REJECT,
            '5.7.1' => self::CAT_USER_REJECT,
        );

        if (!isset($ruleCatStatusCode[$statusCode])) {
            return null;
        }

        return self::getRuleCat($ruleCatStatusCode[$statusCode]);
    }

    /**
     * Find the recipient e-mail.
     *
     * @param array $rcpt : the recipient headers
     *
     * @return string
     */
    protected static function findEmail($rcpt)
    {
        if (isset($rcpt['Original-recipient']) && !self::isEmpty($rcpt['Original-recipient'], 'addr')) {
            return self::extractEmail($rcpt['Original-recipient']['addr']);
        } elseif (isset($rcpt['Final-recipient']) && !self::isEmpty($rcpt['Final-recipient'], 'addr')) {
            return self::extractEmail($rcpt['Final-recipient']['addr']);
        }

        return null;
    }

    /**
     * Find the e-mail(s) from the body section first.
     *
     * @param string $bodySectionFirst : the body section first
     *
     * @return array
     */
    protected static function findEmails($bodySectionFirst)
    {
        $result = array();

        if (empty($bodySectionFirst)) {
            return $result;
        }

        $arBodySectionFirst = explode("\r\n", $bodySectionFirst);
        foreach ($arBodySectionFirst as $line) {
            if (preg_match('/\b([A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/i', $line, $match)) {
                if (!in_array($match[1], $result)) {
                    $result[] = $match[1];
                }
            }
        }

        return $result;
    }

    /**
     * Format the content of a message.
     *
     * @param string $content : a generic content
     *
     * @return string
     */
    protected static function formatEmailContent($content)
    {
        if (empty($content)) {
            return $content;
        }

        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\n", "\r\n", $content);
        $content = str_replace("=\r\n", '', $content);
        $content = str_replace('=3D', '=', $content);
        $content = str_replace('=09', '  ', $content);

        return $content;
    }

    /**
     * Extract email from string.
     *
     * @param string
     *
     * @return string
     */
    protected static function extractEmail($string)
    {
        $result = $string;
        $arResult = preg_split('#[ \"\'\<\>:\(\)\[\]]#', $string);
        foreach ($arResult as $result) {
            if (strpos($result, '@') !== false) {
                return $result;
            }
        }

        return $result;
    }

    protected static function formatUnixPath($path)
    {
        return str_replace('\\', '/', $path);
    }

    protected static function endWith($string, $search)
    {
        $length = strlen($search);
        $start = $length * -1;

        return substr($string, $start) === $search;
    }

    protected static function isEmpty($value, $key = '')
    {
        if (!empty($key) && is_array($value)) {
            return !array_key_exists($key, $value) || empty($value[$key]);
        } else {
            return !isset($value) || empty($value);
        }
    }

    /**
     * Check if open mode is mailbox.
     *
     * @return bool
     */
    public function isMailboxOpenMode()
    {
        return $this->openMode == self::OPEN_MODE_MAILBOX;
    }

    /**
     * Check if open mode is file.
     *
     * @return bool
     */
    public function isFileOpenMode()
    {
        return $this->openMode == self::OPEN_MODE_FILE;
    }

    /**
     * Check if process mode is neutral mode.
     *
     * @return bool
     */
    public function isNeutralProcessMode()
    {
        return $this->processMode == self::PROCESS_MODE_NEUTRAL;
    }

    /**
     * Check if process mode is move mode.
     *
     * @return bool
     */
    public function isMoveProcessMode()
    {
        return $this->processMode == self::PROCESS_MODE_MOVE;
    }

    /**
     * Check if process mode is delete mode.
     *
     * @return bool
     */
    public function isDeleteProcessMode()
    {
        return $this->processMode == self::PROCESS_MODE_DELETE;
    }

    /**
     * The method to process bounces.
     *
     * @return string $processMode
     */
    public function getProcessMode()
    {
        return $this->processMode;
    }

    /**
     * Set the method to process bounces to neutral.
     */
    public function setNeutralProcessMode()
    {
        $this->setProcessMode(self::PROCESS_MODE_NEUTRAL);
    }

    /**
     * Set the method to process bounces to move.
     */
    public function setMoveProcessMode()
    {
        $this->setProcessMode(self::PROCESS_MODE_MOVE);
    }

    /**
     * Set the method to process bounces to delete.
     */
    public function setDeleteProcessMode()
    {
        $this->setProcessMode(self::PROCESS_MODE_DELETE);
    }

    /**
     * Set the method to process bounces.
     *
     * @param string $processMode
     */
    protected function setProcessMode($processMode)
    {
        $this->processMode = $processMode;
    }

    /**
     * Mailbox service.
     *
     * @return string $mailboxService
     */
    public function getMailboxService()
    {
        return $this->mailboxService;
    }

    /**
     * Set the mailbox service to IMAP.
     */
    public function setImapMailboxService()
    {
        $this->setMailboxService(self::MAILBOX_SERVICE_IMAP);
    }

    /**
     * Set the mailbox service.
     *
     * @param string $mailboxService
     */
    protected function setMailboxService($mailboxService)
    {
        $this->mailboxService = $mailboxService;
    }

    /**
     * Mailbox host server.
     *
     * @return string $mailboxHost
     */
    public function getMailboxHost()
    {
        return $this->mailboxHost;
    }

    /**
     * Set the mailbox host server.
     *
     * @param string $mailboxHost
     */
    public function setMailboxHost($mailboxHost)
    {
        $this->mailboxHost = $mailboxHost;
    }

    /**
     * The username of mailbox.
     *
     * @return string $mailboxUsername
     */
    public function getMailboxUsername()
    {
        return $this->mailboxUsername;
    }

    /**
     * Set the username of mailbox.
     *
     * @param string $mailboxUsername
     */
    public function setMailboxUsername($mailboxUsername)
    {
        $this->mailboxUsername = $mailboxUsername;
    }

    /**
     * Set the password needed to access mailbox.
     *
     * @param string $mailboxPassword
     */
    public function setMailboxPassword($mailboxPassword)
    {
        $this->mailboxPassword = $mailboxPassword;
    }

    /**
     * The mailbox server port number.
     *
     * @return int $mailboxPort
     */
    public function getMailboxPort()
    {
        return $this->mailboxPort;
    }

    /**
     * Set the mailbox server port number to POP3 (110).
     */
    public function setMailboxPortPop3()
    {
        $this->setMailboxPort(self::MAILBOX_PORT_POP3);
    }

    /**
     * Set the mailbox server port number to POP3 TLS/SSL (995).
     */
    public function setMailboxPortPop3TlsSsl()
    {
        $this->setMailboxPort(self::MAILBOX_PORT_POP3_TLS_SSL);
    }

    /**
     * Set the mailbox server port number to IMAP (143).
     */
    public function setMailboxPortImap()
    {
        $this->setMailboxPort(self::MAILBOX_PORT_IMAP);
    }

    /**
     * Set the mailbox server port number to IMAP TLS/SSL (993).
     */
    public function setMailboxPortImapTlsSsl()
    {
        $this->setMailboxPort(self::MAILBOX_PORT_IMAP_TLS_SSL);
    }

    /**
     * Set the mailbox server port number.
     *
     * @param int $mailboxPort
     */
    public function setMailboxPort($mailboxPort)
    {
        $this->mailboxPort = $mailboxPort;
    }

    /**
     * The mailbox security option.
     *
     * @return string $mailboxSecurity
     */
    public function getMailboxSecurity()
    {
        return $this->mailboxSecurity;
    }

    /**
     * Set the mailbox security option.
     *
     * @param string $mailboxSecurity
     */
    public function setMailboxSecurity($mailboxSecurity)
    {
        $this->mailboxSecurity = $mailboxSecurity;
    }

    /**
     * Certificate validation.
     *
     * @return string $mailboxCert
     */
    public function getMailboxCert()
    {
        return $this->mailboxCert;
    }

    /**
     * Set the certificate validation to VALIDATE.
     */
    public function setMailboxCertValidate()
    {
        $this->setMailboxCert(self::MAILBOX_CERT_VALIDATE);
    }

    /**
     * Set the certificate validation to NOVALIDATE.
     */
    public function setMailboxCertNoValidate()
    {
        $this->setMailboxCert(self::MAILBOX_CERT_NOVALIDATE);
    }

    /**
     * Set the certificate validation.
     *
     * @param string $mailboxCert
     */
    protected function setMailboxCert($mailboxCert)
    {
        $this->mailboxCert = $mailboxCert;
    }

    /**
     * Mailbox name.
     *
     * @return string $mailboxName
     */
    public function getMailboxName()
    {
        return $this->mailboxName;
    }

    /**
     * Set the mailbox name, other choices are (Tasks, Spam, Replies, etc.).
     *
     * @param string $mailboxName
     */
    public function setMailboxName($mailboxName)
    {
        $this->mailboxName = $mailboxName;
    }

    /**
     * The resource handler for the opened mailbox (POP3/IMAP/NNTP/etc.).
     *
     * @return resource $handler
     */
    public function getMailboxHandler()
    {
        return $this->mailboxHandler;
    }

    /**
     * Maximum limit messages processed in one batch.
     *
     * @return int $maxMessages
     */
    public function getMaxMessages()
    {
        return $this->maxMessages;
    }

    /**
     * Set the maximum limit messages processed in one batch (0 for unlimited).
     *
     * @param number $maxMessages
     */
    public function setMaxMessages($maxMessages)
    {
        $this->maxMessages = $maxMessages;
    }

    /**
     * Check if purge unknown messages.
     *
     * @return bool $purge
     */
    public function isPurge()
    {
        return $this->purge;
    }

    /**
     * Set the purge unknown messages. Be careful with this option.
     *
     * @param bool $purge
     */
    public function setPurge($purge)
    {
        $this->purge = $purge;
    }

    /**
     * The last error message.
     *
     * @return string $error
     */
    public function getError()
    {
        return $this->error;
    }
}
