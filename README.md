# invent2023

This software is a basic inventory / POS system, specifically designed for Auroville, an international city in South India

The software was created in 2005, without using a PHP framework.

The software will eventually be replaced, using the Laravel framework.


# installation

- download and install git from git-scm.com
- git clone <repository>
- copy config files from older version
- replace 'v7' with '2023' in the paths
- add 'mysql_folder' to config.ini
- add define('BILL_UPI',8) and define('BILL_BANK_TRANSFER',9) to const.inc.php
- and define('ACCOUNT_SOAP_SERVER_URL','http://api3.avfs.org.in/server3.php');
- set or add define('DOWNLOAD_ALL', 0) in config.inc.php; for PTPS this should be 1
- run updatemanager/mysql_update.php
- copy print_bill.php / export_invoice.php / export_proforma.php
- check backups
- open httpd.conf, set LogLevel to crit
- open php.ini, set log_errors = Off

# git
if local changes are temporary, and the repository has been updated then:
1. git stash push
2. git stash drop
3. and then git pull

or simply: git reset --hard and then git pull

# Kalki Aplhion router
Services > Firewall > Port Forwarding
Current Port Forwarding Table
WebServer | 192.168.1.1 | TCP | 80 | 192.168.1.126 | 80
Services > Service > Dynamic DNS
