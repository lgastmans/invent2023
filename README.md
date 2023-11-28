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
- run updatemanager/mysql_update.php
- copy print_bill.php / export_invoice.php / export_proforma.php
- check backups
- open httpd.conf, and set LogLevel to crit

# git
if local changes are temporary, and the repository has been updated then:
1. git stash push
2. git stash drop
3. and then git pull

or simply: git reset --hard and then git pull
