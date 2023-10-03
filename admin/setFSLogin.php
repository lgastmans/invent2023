<?php
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once('db_params.php');
	
	if (IsSet($_POST['save'])) {
		if ($_POST['fs_password']==='AnandP-2011') {
			$fs_pin = base64_encode($_POST['fs_pin']);
			$fs_pid = base64_encode($_POST['fs_pid']);
			
			$str_query = "
				UPDATE user_settings
				SET
					 application_pin = '$fs_pin',
					 application_pid = '$fs_pid'
			";
			$qry =& $conn->query($str_query);
			
			if (MDB2::isError($conn))
				echo "Error<br>".$conn->getDebugInfo();
			else {
				echo "Update successfully";
				echo "<script>\n";
				echo "setTimeout('window.close();',2000)";
				echo "</script>\n";
			}
		}
		else echo "Incorrect authorization password";
	}
?>
<html>
<head>
	<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>
<body id="myBody">
	<form name="setFSLogin" method="POST">
		PIN: <input type="text" name="fs_pin" value="<?php echo $_POST['fs_pin']?>">
		<br>
		
		PID: <input type="text" name="fs_pid" value="<?php echo $_POST['fs_pid']?>">
		<br>
		
		Password: <input type="password" name="fs_password" value="">
		<br>
		
		<input type="submit" name="save" value="set">
	</form>
</body>
</html>