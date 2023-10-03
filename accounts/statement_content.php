<?php
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_params.php");
	require_once("db.inc.php");
	require_once("db_funcs.inc.php");

	$db_server = $arr_invent_config['database']['invent_server'];
	$db = $arr_invent_config['database']['invent_database'];
	$db_login = $arr_invent_config['database']['invent_login'];
	$db_password = $arr_invent_config['database']['invent_password'];
	
	$account = '';
	if (isSet($_GET['account'])) {
		$account = $_GET['account'];
	}
	
	if (isSet($_GET["filter_from"])) {
		$arr_period = explode("_", $_GET["filter_from"]);
		$int_start = intval($arr_period[1]);
		$month_start = intval($arr_period[0]);
	}
	else {
		$int_start = date('Y');
		$month_start = date('n');
	}

	if (isSet($_GET["filter_to"])) {
		$arr_period = explode('_', $_GET['filter_to']);
		$int_end = intval($arr_period[1]);
		$month_end = intval($arr_period[0]);
	}
	else {
		$int_end = date('Y');
		$month_end = date('n');
	}
	
	if ($int_end == $int_start) {
		$int_columns = ($month_end - $month_start) + 1;
	}
	else {
		$int_columns = (12 - $month_start) + (($int_end - $int_start -1) * 12) + $month_end;
	}
	
	$sql = "SELECT * FROM account_cc ac WHERE ac.account_number = '$account'";
	$qry =& $conn->query($sql);
	if ($qry->b_error == true) {
		$error = true;
		die($qry->err);
	}


	$ac_name = "";
	if ($qry->num_rows>0) {
		$obj = $qry->fetch_object();
		$ac_name = $obj->account_name;
	}
	

	function getStatus($int) {
		switch ($int) {
			case 0:
				return "PENDING";
				break;
			case 1:
				return "INSUFFICIENT FUNDS";
				break;
			case 2:
				return "ERROR";
				break;
			case 3:
				return "CANCELLED";
				break;
			case 4:
				return "HOLD";
				break;
			case 5:
				return "COMPLETE";
				break;
			case 6:
				return "REVIEW";
				break;
		}
	}	
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
	<style>
		.normalnumber {
			font-family:Verdana,sans-serif;
			font-size:11px;
			color:black;
			text-align:right;
		}
		
		td {
			border:.5px;
			border-style:groove;
			border-color:grey;
		}
		
		.blank {
			border-bottom-width:0px;
			border-bottom-style:none;
			border-top-width:0px;
			border-top-style:none;
			border-right-width:0px;
			border-right-style:none;
			background-color:#E1E1E1;
		}
	</style>
</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<h2>
<?php
	echo "Account details for $account - $ac_name";
?>
</h2>

<table>
<?php
	
	$int_start_month = $month_start;
	$int_start_year = $int_start;
	$total = 0;
	
	while (true) {

		echo "<tr>";
		echo "<td colspan='10'>";
		echo "<b>".getMonthName($int_start_month)." ".$int_start_year."</b>";
		echo "</td>";
		echo "</tr>";

		$str_query = "SELECT DAY(tr.date_created) AS date_created, tr.amount, tr.description, tr.transfer_status, u.username, ac.account_name 
			FROM  account_transfers_".$int_start_year."_".$int_start_month." tr 
			INNER JOIN user u ON u.user_id = tr.user_id 
			INNER JOIN account_cc ac ON (ac.cc_id = tr.cc_id_from) AND (tr.transfer_status IN (0,1,2,3,4,5,6))
			WHERE ac.account_number = '$account'";
					
		$qry =& $conn->query($str_query);

		if ($qry->b_error == true) {

			$error = true;
			die($qry->err);

		}
		

		if ($qry->num_rows > 0) {

			$month_total = 0;

			while ($obj = $qry->fetch_object()) {

				echo "<tr>";
				echo "<td width='50px' align='right'>".$obj->date_created."</td>";
				echo "<td width='100px' align='right'>".$obj->amount."</td>";
				echo "<td width='250px' align='left'>".$obj->description."</td>";
				echo "<td width='100px' align='left'>".getStatus($obj->transfer_status)."</td>";
				echo "</tr>";
				
				$total += $obj->amount;
				$month_total += $obj->amount;
			}

			echo "<tr>";
			echo "<td colspan='4'>".number_format($month_total,2)."</td>";
			echo "</tr>";
		}
		
		if (($int_start_month == 12) && ($int_end > $int_start_year)) {
			$int_start_year++;
			$int_start_month = 1;
		}
		else 
			$int_start_month++;
			
		if ($int_end == $int_start_year)
			if ($int_start_month > $month_end)
				break;
	}

?>
</table>

<?php
	echo "<br><h2>Grand Total: ".number_format($total,2)."</h2>";
?>

</body>
</html>
