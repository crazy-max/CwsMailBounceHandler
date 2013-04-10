CwsBounceMailHandler
====================

CwsBounceMailHandler is a PHP toolkit : (CwsBounceMailHandler and CwsBounceMailHandlerRules) forked from [PHPMailer-BMH (Bounce Mail Handler) v5.0.0rc1](http://phpmailer.codeworxtech.com) by Andy Prevost to help webmasters handle bounce-back mails in standard DSN (Delivery Status Notification, RFC-1894).
It checks your IMAP/POP3 inbox and delete all 'hard' bounced emails.
A result var is available to process custom post-actions.

Installation
------------

* Enable the [php_imap](http://php.net/manual/en/book.imap.php) extension.
* Copy the ``class.cws.bouncemailhandler.php`` and ``class.cws.bouncemailhandler.rules.php`` files in a folder on your server.
* You can use the sample ``index.php`` file to help you.

Options
-------

Public vars :

* **host** - Mail host server.
* **username** - The username of mailbox.
* **password** - The password needed to access mailbox.
* **port** - Defines port number, other common choices are '110' (pop3), '143' (imap), '995' (tls/ssl).
* **service** - Defines service, choice includes 'pop3' and 'imap'.
* **service_option** - Defines service option, choices are 'none', 'notls', 'tls', 'ssl'.
* **cert** - Control certificates validation if service_option is 'tls' or 'ssl'.
* **use_fetchstructure** - Control the method to process the mail header.
* **boxname** - Mailbox type, other choices are Tasks, Spam, Replies, etc.
* **move_soft** - Determines if soft bounces will be moved to another mailbox folder.
* **boxname_soft** - Mailbox folder to move soft bounces to.
* **move_hard** - Determines if hard bounces will be moved to another mailbox folder. NOTE: If true, this will disable delete and perform a move operation instead.
* **boxname_hard** - Mailbox folder to move hard bounces to.
* **max_messages** - Maximum limit messages processed in one batch.
* **error_msg** - The last error message.
* **test_mode** - Test mode, if true will not delete messages.
* **purge** - Purge unknown messages. Be careful with this option.
* **debug_verbose** - Control the debug output.
* **disable_delete** - If true, it will disable the delete function.
* **result** - Result array of process.

Public methods :

* **openRemote** - Open a remote mail box.
* **openLocal** - Open a mail box in local file system.
* **processMailbox** - Process the messages in a mailbox.
