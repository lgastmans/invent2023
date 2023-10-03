<?php
	require_once('../include/const.inc.php');
	require_once('session.inc.php');
	require_once('db_params.php');
	
	$db_db =  $arr_invent_config['database']['invent_database'];
	
	function check_ext($str_search) {
		$retval = false;
		
		$pos = strrpos(strtolower($str_search), ".");
		
		if ($pos > 0) {
			$str_type = strtoupper(substr($str_search, ($pos+1), strlen($str_search)));
			if ($str_type === 'SQL')
				$retval = true;
		}
		
		return $retval;
	}
	
	if (!empty($_FILES['accounts_file']['tmp_name'])) {
		if (is_uploaded_file($_FILES['accounts_file']['tmp_name'])) {
			$bool = check_ext($_FILES['accounts_file']['name']);
			
			if ($bool) {
				$query = file_get_contents($_FILES['accounts_file']['tmp_name']);
				
				$qry =& $conn->query("TRUNCATE TABLE accounts_cc");
				
				exec("mysql -u$db_login -p$db_password $db_db < '".$_FILES['accounts_file']['tmp_name']."'");
				
				echo "<script language='javascript'>";
				echo "window.close()";
				echo "</script>";
			}
			else
				echo "<font color='red'>Incorrect file type</font>";
		}
	}
?>

<html>
<head>
	<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>
<body>
	<form name='accountloadfile' method='POST' enctype='multipart/form-data'>
		<input type="file" name="accounts_file">
		<input type="submit" name="action" value="Load">
	</form>
</body>
</html>