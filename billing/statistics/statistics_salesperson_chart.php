<?php
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_params.php");

	if (isSet($_GET["filter_from"])) {
		$arr_period = explode("_", $_GET["filter_from"]);
		$startYear = intval($arr_period[1]);
		$startMonth = intval($arr_period[0]);
	}
	else {
		$startYear = date('Y');
		$startMonth = date('n');
	}

	if (isSet($_GET["filter_to"])) {
		$arr_period = explode('_', $_GET['filter_to']);
		$endYear = intval($arr_period[1]);
		$endMonth = intval($arr_period[0]);
	}
	else {
		$endYear = date('Y');
		$endMonth = date('n');
	}
	
	$beginDate = $startYear."-".$startMonth."-01";
	$endDate = $endYear."-".$endMonth."-01";

	$range = getRange($beginDate, $endDate);

	$months = array();
	$data = array();
	foreach ($range as $value) {
		$str = "SELECT SUM(total_amount) AS total
			FROM bill_".$value['year']."_".$value['month']." b
		";
		
		$qry =& $conn->query($str);
		$row = $qry->fetchRow();
		
		$data[] = array(
			'name' => getMonthName($value['month']),
			'data1' => round($row->total)
		);
	}
	//echo json_encode($data);

	function getRange($beginDate, $endDate) {
		$startMonth = date('n', strtotime($beginDate));
		$startYear = date('Y', strtotime($beginDate));
		$endMonth = date('n', strtotime($endDate));
		$endYear = date('Y', strtotime($endDate));
		
		$res = array();
		$i=0;
		while (true) {
			
			$res[$i]['month']=$startMonth;
			$res[$i]['year']=$startYear;
				
			if (($startMonth == $endMonth) && ($startYear == $endYear))
				break;
			else {
				if ($startMonth==12) {
					$startMonth=1;
					$startYear++;
				}
				else {
					$startMonth++;
				}
			}
			$i++;
		}
		return $res;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title>Column Chart</title>
	<link rel="stylesheet" type="text/css" href="../../extjs-4.0.0/resources/css/ext-all.css" />
	<script type="text/javascript" src="../../extjs-4.0.0/bootstrap.js"></script>

	<script>
		var check;
		
		Ext.require(['Ext.data.*']);
		
		Ext.onReady(function() {
			window.store1 = Ext.create('Ext.data.JsonStore', {
				fields: ['name', 'data1'],
				data: <?php echo json_encode($data);?> 
			});
		});
	</script>
	<script type="text/javascript" src="Column.js"></script>

</head>
	<body id="docbody">

	</body>
</html>
