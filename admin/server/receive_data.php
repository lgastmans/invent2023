<?php
	echo "checking for orders on the server<br>";
	require('receive_xml.php');
	echo "completed checking for orders<br>";
	
	echo "preparing to import new orders<br>";
	require('import_orders.php');
	echo "completed importing orders<br>";

?>