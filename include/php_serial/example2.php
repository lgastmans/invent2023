<?php
// Set timeout to 500 ms
$timeout=microtime(true)+0.5;

// Set device controle options (See man page for stty)
exec("/bin/stty -F /dev/ttyUSB0 19200 sane raw cs8 hupcl cread clocal -echo -onlcr ");
   
// Open serial port
$fp=fopen("/dev/ttyUSB0","c+");
if(!$fp) die("Can't open device");

// Set blocking mode for writing
stream_set_blocking($fp,1);
fwrite($fp,"foo\n");

// Set non blocking mode for reading
stream_set_blocking($fp,0);
do{
  // Try to read one character from the device
  $c=fgetc($fp);

  // Wait for data to arive
  if($c === false){
      usleep(50000);
      continue;
  } 
 
  $line.=$c;
   
}while($c!="\n" && microtime(true)<$timeout);
 
echo "Responce: $line"; 
?>