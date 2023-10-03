<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");


	if (IsSet($_SESSION['current_discount']))
		$int_discount = $_SESSION['current_discount'];
	else
		$int_discount = 0;

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == "set_type") {
			$_SESSION['current_bill_day'] = $_GET['bill_day'];
		}
		if ($_GET['action'] == "set_discount") {
			$_SESSION['current_discount'] = $_GET["discount"];
			$int_discount = $_GET["discount"];
		}
	}

?>
<!DOCTYPE html>

<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="../../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

	<link rel="stylesheet" type="text/css" href="../../include/datatables/datatables.min.css"/>

	
    <style>
    	body {
    		margin:5px;
    		/*background-color: lightblue;*/
    	}

		h2 { 
			margin-top: 5px;
			margin-bottom: 5px
		}

		.row-bg {
			background-color: lightgrey;
			border-radius: 7px;
			padding: 7px;
			margin-top: 5px;
			margin-bottom: 5px
		}

		#billing_grid {
			background-color: lightgrey;
			border-radius: 7px;
			padding: 7px;
			min-height: 250px;
		}
		th { font-size: 12px; }
		td { font-size: 11px; }

		.address {
			font-size: 10px;
			font-style: italic;
		}

    	.typeahead__list {
    		min-width:300px !important;
    	}

    	#result-container-description {
    		font-style: normal;
    	}
	</style>

</head>

<body> 

  	<!--
  		GRID
  	-->
  	<div id="billing_grid">

		<table id="grid-products" class="table table-striped table-condensed " cellspacing="0" width="100%">

	        <thead>
	            <tr>
	            	<th>id</th>
	                <th>Code</th>
 	                <th>Batch</th>
 	                <th>Inv No</th>
 	                <th>Inv Dt</th>
	                <th>Description</th>
	                <th>Qty</th>
	                <th>B Price</th>
	                <th>S Price</th>
	                <th>Tax</th>
	                <th>Total</th>
	            </tr>
	        </thead>

	    </table>

	    <span id="table_details"></span>

  	</div> <!-- grid -->

    <script src="../../include/js/jquery-3.2.1.min.js"></script>

    <script src="../../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

	<script type="text/javascript" src="../../include/datatables/datatables.min.js"></script>

    <script>

    	var billTable = null;

		$(document).ready(function(){

			billTable = $('#grid-products').DataTable({
		        scrollY 		: '40vh',
		        scrollCollapse	: true,
		        paging			: false,
		        searching		: false,
		        ajax			: "update_list.php",
		        columns: [
	                { data: "id", visible: false },
	                { data: "code" },
	                { data: "batch" },
	                { data: "invno" },
	                { data: "invdt" },
	                { data: "description" },
	                { data: "quantity" },
	                { data: "bprice" },
	                { data: "sprice" },
	                { data: "tax" },
	                { data: "total" }
	            ]
		    });


			billTable.ajax.reload(function(data){

            	console.log(data.billtotal);

            	parent.frames["frame_total"].document.location="receipt_total.php?total="+data.billtotal+"&discount=<? echo $int_discount; ?>";

        	}, false);

		}); // end document ready

		

    </script>



</body>
</html>