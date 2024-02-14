RUN services.msc - set startup to automatic

Set PHP version to 7.4
Disable PHP extension SOAP

copy php_mcrypt.dll to php folder: \bin\php\php7.4\ext

PHP.INI
=======
add extension=mcrypt
enable extension=intl

set 
	short_open_tag		ON
	date.timezone		Asia/Kolkata
	memory_limit		512
	max_execution_time	3600
	max_input_vars		10000
	max_input_time		1800

my.ini
======
set sql_mode=NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION


Wampserver Icon > Apache > httpd-vhosts.conf
Change line "Require local" to "Require all granted"

Apache Conf
===========
set LogLevel	"crit"


Github
======
download and install git from git-scm.com
git clone https://github.com/lgastmans/invent2023.git
copy config files from older version
replace 'v7' with '2023' in the paths
add 'mysql_folder' to config.ini
add define('BILL_UPI',8) and define('BILL_BANK_TRANSFER',9) to const.inc.php
run updatemanager/mysql_update.php
copy print_bill.php / export_invoice.php / export_proforma.php
check backups


Invent
======
edit:
	config.ini
	const.inc.php
	config.inc.php (api3.avfs.org.in/server3.php)
	mysql_backup.php (set the correct path for the exe)


Control Panel > Firewall > Allow app through firewall > Allow some app > C:/wamp64/bin/apache2/bin/httpd.exe 
(maybe "change settings" needs to be clicked)
(If Kaspersky is installed not required...)

CHECK BACKUPS
PRINTER - EscPos and print.php (as Shared "Receipt")




==== MySQL error ====
Error: Unable to connect to MySQL. Debugging error: 2002. Only one usage of each socket address (protocol/network address/pprt) is normally permitted.

Solution
--------
Open regedit
HKLM/SYSTEM/CurrentControlSet/Services/Tcpip/Parameters
create 4 new DWORD with these key and values:

TcpTimedWaitDelay
REG_DWORD	0000001e (hex)

MaxUserPort
REG_DWORD	0000fffe (hex)

TxpNumConnections
REG_DWORD	00fffffe (hex)

TcpMaxDataRetransmissions
REG_DWORD	00000005 (hex)


