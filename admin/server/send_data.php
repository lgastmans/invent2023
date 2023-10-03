<?php
	echo "preparing to create files to send to server<br>";
	require('create_xml.php');
	echo "completed creating files<br>";
	
	echo "preparing to send files to server<br>";
	require('send_xml.php');
	echo "completed sending files to server<br>";

?>