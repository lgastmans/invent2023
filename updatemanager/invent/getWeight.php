<?php 
//Needs to have DIO (pecl) extension installed to work.
//The PHP class was not working properly.so PECL was the solution to my big problem.

$fd = dio_open('/dev/ttyS0', O_RDONLY);
$weight=dio_read($fd);
echo $weight;
dio_close($fd);
?>