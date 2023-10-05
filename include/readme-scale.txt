How to make the scale work in INVENT

on the client: 

aptitude install php5-dev apache2 php-pear libapache2-mod-php5 
pecl config-set preferred_state beta 
pecl install dio 
Edit /etc/php/apache2/php.ini 
Add "extension=dio.so" 
Edit /etc/apache2/sites-available/default 

<VirtualHost *:80> 
ServerAdmin webmaster@localhost 
ServerName vegetable.ptdc.av 
DocumentRoot /var/www/ptdc_scale/ 
<Directory /> 
Options FollowSymLinks 
AllowOverride None 
</Directory> 

</VirtualHost> 

edit file /etc/group 
locate the dialout group and add www-data 

on the server: 

Edit /var/www/invent/include/config.inc.php 
Add '192.168.10.11'=>'vegetable.prosperity.av','192.168.10.11'=>'vegetable.prosperity.av' to the weight_clients array 
Edit /var/www/invent/include/config.ini 
Change use_scale=N to use_scale=Y 

aptitued install php5-curl 

Edit /etc/php/apache2/php.ini 
Add "extension=php_curl.so" 

we used the two new files from /root/ptdc: 

billing_enter.php 
bill_new.php 

both to the ../invent/billing folder 

installed jquerry to ../invent/include/js 

