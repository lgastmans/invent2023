<?php 
	$calc_price = "BP";
	if (IsSet($_GET['price']))
		$calc_price = $_GET['price'];

	$str_include_tax = 'Y';
	if (IsSet($_GET['include_tax']))
		$str_include_tax = $_GET['include_tax'];

	$str_order_by = 'b.date_created';
	if (IsSet($_GET['order_by'])) {
		if ($_GET['order_by'] == 'date')
			$str_order_by = 'b.date_created, sp.product_code';
		else if ($_GET['order_by'] == 'code')
			$str_order_by = 'sp.product_code';
	}

	$where_filter_day = "";
	if (IsSet($_GET['filter_day']) && ($_GET['filter_day']!='ALL'))
		$where_filter_day = "AND (DAYOFMONTH(b.date_created)=".$_GET['filter_day'].") ";	
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Bootstrap -->
    <link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
		.loader {
			border: 16px solid #f3f3f3; /* Light grey */
			border-top: 16px solid #3498db; /* Blue */
			border-radius: 50%;
			width: 120px;
			height: 120px;
			animation: spin 2s linear infinite;
		}

		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
	</style>

</head>

<body style="margin-top: 20px;">

	<div class="container">

	  	<div class="row">

			<div class="panel panel-primary">

				<div class="panel-heading">
					Email Details
				</div>

				<div class="panel-body">
				  	<div class="col-md-2">
				  	</div>

				  	<div class="col-md-8">
				
				 		<div class="loader"></div> 
				
				 		<div id="email" style="display:none">

				 			<p>
				 				<span id="msg"></span>
				 			</p>

					  	</div>

					</div>

				  	<div class="col-md-2">
				  	</div>

				</div> <!-- panel-body -->

			</div> <!-- panel -->

		</div> <!-- row -->

	</div>


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../include/js/jquery-3.2.1.min.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

    <script>

		$(document).ready(function(){

			$.ajax({
				method 	: "GET",
				url 	: "statements_email_send.php",
				data 	: { "action" : "send", 
					supplier_id : <?php echo $_GET['supplier_id'];?>,
					include_tax : '<?php echo $str_include_tax;?>',
					order_by	: '<?php echo $_GET['order_by'];?>',
					format 		: '<?php echo $_GET['format'];?>',
					price 		: '<?php echo $calc_price; ?>',
					filter_day	: '<?php echo $_GET['filter_day'];?>'
				}
			})
			.done( function( msg ) {
				console.log(msg);

				$(".loader").hide();

				$("#email").show();

				$("#msg").html(msg);
			});

		});

    </script>

</body>
</html>