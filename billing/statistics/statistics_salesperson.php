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

	$salesperson_id = 0;
	if ($_GET['salesperson_id'])
		$salesperson_id = $_GET['salesperson_id'];

	$range = getRange($beginDate, $endDate);

	$months = array();
	$data = array();
	foreach ($range as $value) {
		if ($salesperson_id!='ALL')
			$str = "SELECT sp.id, sp.first, sp.last, SUM(total_amount) AS total
				FROM bill_".$value['year']."_".$value['month']." b
				LEFT JOIN salespersons sp ON (sp.id = b.salesperson_id)
				WHERE b.salesperson_id = $salesperson_id
				GROUP BY b.salesperson_id
				ORDER BY total DESC
			";
		else
			$str = "SELECT sp.id, sp.first, sp.last, SUM(total_amount) AS total
				FROM bill_".$value['year']."_".$value['month']." b
				LEFT JOIN salespersons sp ON (sp.id = b.salesperson_id)
				GROUP BY b.salesperson_id
				ORDER BY total DESC
			";
		$qry =& $conn->query($str);
		$i=0;
		while ($row = $qry->fetch_row()) {
			$data[$row->id]['first'] = $row->first;
			$data[$row->id]['last'] = $row->last;
			$data[$row->id]['months'][getMonthName($value['month'])] = $row->total;
			$i++;
		}
		$months[] = getMonthName($value['month']);
	}
	
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

</head>
	<body>

		<font style="font-family:Verdana,sans-serif;">
		<table border="1" cellpadding="7" cellspacing="0">
			<tr bgcolor="#C4C4C4">
				<td><b>Salesperson</b></td>
				<?php
					foreach ($months as $name) {
						echo "<td><b>$name</b></td>";
					}
				?>
			</tr>
			<?php 
				$i=0;
				foreach($data as $value) {
					if ($i % 2 == 1) 
						$bgcolor = "#dfdfdf";
					else 
						$bgcolor = "#ffffff"; 
	
					echo "<tr bgcolor=".$bgcolor.">";
					echo "<td>".$value['first']." ".$value['last']."</td>";
					foreach ($months as $name) {
						echo "<td>".number_format($value['months'][$name],2,'.',',')."</td>";
					}
					echo "</tr>";
					$i++;
				}
			?>
		</table>
		</font>
	</body>
</html>
