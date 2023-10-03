<html><body>
<? 
die('here');//echo "hello, world";

if (!empty($_POST['data'])) {

	$fn = '/tmp/fp'.rand(1000,9999);

	echo $_POST['data'];
	$str_data = rawurldecode($_POST['data']);

	echo "creating file $fn";
 	$f = fopen($fn,"w+");
	fwrite($f,$str_data);
	fclose($f);

	$res=exec("lpr $fn -oraw");

	//echo "Printing successfully completed! - $res<script language=javascript>window.close(); </script>";

} else { echo "Print error - no data passed!"; 
	foreach ($_POST as $key => $value) {
		echo $key ."=".$value;
	}
}


?>
</body></html>