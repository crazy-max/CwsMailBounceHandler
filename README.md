CwsMailBounceHandler
====================

CwsMailBounceHandler is a PHP class to help webmasters handle bounce-back, feedback loop and ARF mails in standard DSN (Delivery Status Notification, RFC-1894).
It checks your IMAP/POP3 inbox or eml files and delete or move all 'hard' bounced emails.
If a bounce is malformed, it tries to extract some useful information to parse status.
A result array is available to process custom post-actions.

Installation
------------

* Enable the [php_imap](http://php.net/manual/en/book.imap.php) extension if you want to use the IMAP open mode.
* Copy the ``class.cws.mbh.php`` file in a folder on your server.
* You can use the ``index.php`` file sample and the eml files in the emls directory to help you.

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
* **open_mode** - Control the method to open e-mail(s).
* **boxname** - Mailbox type, other choices are Tasks, Spam, Replies, etc.
* **move_soft** - Determines if soft bounces will be moved to another mailbox or folder.
* **boxname_soft** - Mailbox or folder to move soft bounces to.
* **move_hard** - Determines if hard bounces will be moved to another mailbox or folder. NOTE: If true, this will disable delete and perform a move operation instead.
* **boxname_hard** - Mailbox or folder to move hard bounces to.
* **max_messages** - Maximum limit messages processed in one batch.
* **error_msg** - The last error message.
* **test_mode** - Test mode, if true will not delete messages.
* **purge** - Purge unknown messages. Be careful with this option.
* **debug_verbose** - Control the debug output.
* **disable_delete** - If true, it will disable the delete function.
* **result** - Result array of process.

Public methods :

* **openFolder** - Open a folder containing eml files on your system.
* **openFile** - Open an eml file on your system.
* **openImapLocal** - Open a mail box in local file system.
* **openImapRemote** - Open a remote mail box.
* **processMailbox** - Process the messages in a mailbox.
* **findStatusExplanationsByCode** - Get explanations from DSN status code via the RFC 1893.

More infos
----------

http://www.crazyws.fr/dev/classes-php/classe-de-gestion-des-bounces-en-php-C72TG.html