<?php
	require_once('nusoap.php');

	require_once('../include/const.inc.php');
	require_once('../include/session.inc.php');
	require_once('../include/db_mysqli.php');


/*
	get the transfers data
*/
	$str_transfer_type = 'pending';
	if (IsSet($_GET['transfer_type']))
		$str_transfer_type = $_GET['transfer_type'];

	if ($str_transfer_type == 'pending') {
		$qry = $conn->query(
			"SELECT transfer_id
			FROM ".Monthalize('account_transfers')." 
			WHERE transfer_status=".ACCOUNT_TRANSFER_PENDING."
			LIMIT 0,12
		");
	}
	else {
		$qry = $conn->query(
			"SELECT transfer_id
			FROM ".Monthalize('account_transfers')." 
			WHERE transfer_status=".ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS."
		");
	}


/*
	load to array of transfer_ids to pass to javascript
*/
	$arr = array();
	
	$i=0;
	while ($obj = $qry->fetch_object()) {

		$arr[$i] = array(
			'transfer_id'=>$obj->transfer_id,
		);

		$i++;
	}

	$transfers = json_encode($arr);

//	print_r($transfers);
//	die('test');
?>

<html>
	<head>
		<title>Financial Service Export</title>
		<link rel="stylesheet" href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css">
	</head>
	<body>
		<div class="container-fluid">
			<div class="row">
				<div class="col-md-9">
					<h3><span id="output_title"></span></h3>
					<h4><span id="output_count"></span></h4>
					<ul id="output">
						<!-- output comes here -->
					</ul>
				</div>
			</div>
		</div>
	</body>

    <script src="../include/js/jquery-3.2.1.min.js"></script>

	<script type="text/javascript">


	$(document).ready(function(){

		var transfers = <?php echo json_encode(json_decode($transfers,TRUE)); ?>;
		var total_transfers = transfers.length;
		var counter = 0;

		console.log('total transfers: ' + total_transfers);
		$('#output_title').append('<b>' + total_transfers + ' transfers to process </b>');

		window.transferFS = function()
		{
			
		    $.ajax({
				method	: "POST",
				url		: "get_fs_transfer.php",
				data 	: {
					transfer_id: transfers[counter]['transfer_id']
				},
		        async	: true,
		        success:function(data) {

		            $('#output').append('<li>' + data +'</li>');
		            $('#output_count').html((counter+1) +' out of ' + total_transfers + ' completed.');

					//console.log('transfer message: ' + data);

		            counter++;

		            if (counter < total_transfers) 
		            	transferFS();
		            else
		            	$('#output').append('<li> done. </li>');
		        }
		    });

		}

		transferFS();
		
	});

	</script>
</html>