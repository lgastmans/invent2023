<?php

/*
	https://blog.mypapit.net/2008/05/how-to-use-usb-serial-port-converter-in-ubuntu.html

	In Terminal run: $lsusb
	This gives this output:
	ID 1a86:7523 QinHeng Electronics HL-340 USB-Serial adapter

*/



include "php_serial.class.php";

// Let's start the class
$serial = new phpSerial;

// First we must specify the device. This works on both linux and windows (if
// your linux serial device is /dev/ttyS0 for COM1, etc)
$serial->deviceSet("/dev/ttyUSB0");

$serial->confBaudRate(2400);
$serial->confParity("none");
$serial->confCharacterLength(8);
$serial->confStopBits(1);
$serial->confFlowControl("none");

//print_r($serial);

// Then we need to open it
$res = $serial->deviceOpen();

print_r($res);

// To write into
//$serial->sendMessage("Hello !");

// Or to read from
$read = $serial->readPort();

echo "READ";
print_r($read);

// If you want to change the configuration, the device must be closed
//$serial->deviceClose();

// We can change the baud rate
//$serial->confBaudRate(2400);

// etc...
?>
