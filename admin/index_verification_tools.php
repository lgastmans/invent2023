
<?php

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	
	$qry_supplier = new Query("
		SELECT supplier_id, supplier_name, supplier_phone
		FROM stock_supplier
		WHERE is_active = 'Y'
		ORDER BY supplier_name
	");

	$arr_storeroom_list = getStoreroomList();

	$int_access_level = (getModuleAccessLevel('Admin'));
	
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	} 
	
	$_SESSION['int_admin_selected']=10;
	
	$qry_module = new Query("SELECT * FROM module LIMIT 1");

	if (!isset($_GET['action']))
		$action = 'fs';
	else
		$action = $_GET['action'];
?>

<!DOCTYPE html>
<html lang="en">

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">


<head>

    <!-- Bootstrap -->
    <link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

	<style type="text/css">

		body {
			margin:20px;
		}

		/*
		    Original version: http://www.bootply.com/128062
		    
		    This version adds support for IE 10+ and Firefox.
		*/

		.glyphicon-refresh-animate {
		    -animation: spin .7s infinite linear;
		    -ms-animation: spin .7s infinite linear;
		    -webkit-animation: spinw .7s infinite linear;
		    -moz-animation: spinm .7s infinite linear;
		}

		@keyframes spin {
		    from { transform: scale(1) rotate(0deg);}
		    to { transform: scale(1) rotate(360deg);}
		}
		  
		@-webkit-keyframes spinw {
		    from { -webkit-transform: rotate(0deg);}
		    to { -webkit-transform: rotate(360deg);}
		}

		@-moz-keyframes spinm {
		    from { -moz-transform: rotate(0deg);}
		    to { -moz-transform: rotate(360deg);}
		}		
	</style>

</head>

<body>

	<div class="container">

		<ul class="nav nav-pills">
		  <li role="presentation" <?php echo ($action=='fs' ? 'class="active"' : ''); ?>><a id="fs" href="#">FS Transfers</a></li>
		  <li role="presentation" <?php echo ($action=='prices' ? 'class="active"' : ''); ?>><a id="prices" href="#">Prices</a></li>
		  <li role="presentation" <?php echo ($action=='batches' ? 'class="active"' : ''); ?>><a id="batches" href="#">Batches</a></li>
		  <li role="presentation" <?php echo ($action=='stock' ? 'class="active"' : ''); ?>><a id="stock" href="#">Stock</a></li>
		</ul>

<!-- 		    <div class="col-md-12 text-center">
		        <span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>
		    </div>
 -->
	</div>


	<br><br>




    <!--  FS  -->

	<?php if ($action=='fs') { ?>

		<ul>
			<li>
			<a class='settings_link' id="fs_duplicate" href='#'>Search for duplicate transfers</a>
			</li>

			<li>
			<a class='settings_link' id="fs_verify" href='#'>Verifies whether all the FS account bills marked 'resolved' have corresponding transfers</a>
			</li>

			<li>
				<a class='settings_link' id="fs_transfers" href='#'>Cross check all transfers marked complete with transfers on the Financial Service server for the following day :</a>

				<select id="select_day" name='select_day'>
				<?
					if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
						for ($i=1;$i<=date('d',time());$i++) {
							echo "<option value=$i>".$i."</option>\n";
						}
					}
					else {
						for ($i=1;$i<=DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);$i++) {
							echo "<option value=$i>".$i."</option>\n";
						}
					}
				?>
				</select>
			</li>

		</ul>

	<?php } ?>


    <!--  prices  -->

	<?php if ($action=='prices') { ?>

		<ul>
			<li>
				<a class='settings_link' id="prices_global" href='#'>Set all products to global price</a>
			</li>
		</ul>

	<?php } ?>



    <!--  batches  -->

	<?php if ($action=='batches') { ?>

	<?php } ?>



    <!--  stock  -->

	<?php if ($action=='stock') { ?>

		<ul>
			<li>
				<a class='settings_link' id="reset_sprice" href='#'>Reset selling price for given supplier:</a> 
					<select id="reset_supplier" name="reset_supplier" class='select_400'>
						<?
							for ($i=1; $i<=$qry_supplier->RowCount(); $i++) {

								if ($qry_supplier->FieldByName('supplier_id') == $_SESSION['global_current_supplier_id'])
								    echo "<option value=".$qry_supplier->FieldByName('supplier_id')." selected>".$qry_supplier->FieldByName('supplier_name');
								else
								    echo "<option value=".$qry_supplier->FieldByName('supplier_id').">".$qry_supplier->FieldByName('supplier_name');
								$qry_supplier->Next();
							}
						?>
					</select>
			</li>
			<li>
				<a class='settings_link' id="reset_zero" href='#'>Reset all stock to zero for given supplier:</a> 
					<select id="reset_supplier" name="reset_supplier" class='select_400'>
						<?
							$qry_supplier->First();
							for ($i=1; $i<=$qry_supplier->RowCount(); $i++) {

								if ($qry_supplier->FieldByName('supplier_id') == $_SESSION['global_current_supplier_id'])
								    echo "<option value=".$qry_supplier->FieldByName('supplier_id')." selected>".$qry_supplier->FieldByName('supplier_name');
								else
								    echo "<option value=".$qry_supplier->FieldByName('supplier_id').">".$qry_supplier->FieldByName('supplier_name');
								$qry_supplier->Next();
							}
						?>
					</select>
			</li>
			<li>
				<a class='settings_link' id="closing_balance" href='#'>Check Closing Balances</a>
			</li>
			<li>
				<a href="settings_link" id="move_stock" href="#">Move all stock to storeroom</a>
				<select id="move_storeroom">
					<?
						foreach ($arr_storeroom_list as $key=>$value) {
							if ($key != $_SESSION['int_current_storeroom'])
								echo "<option value='$key'>$value</option>\n";
						}
					?>
				</select>
			</li>
		</ul>


	<?php } ?>





    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="../include/js/jquery-3.2.1.min.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>

    <script>

		$(document).ready(function(){

			$("#batches").click(function() {
				parent.window.frames["content"].location.href = 'index_verification_tools.php?action=batches';
			});

			$("#prices").click(function() {
				parent.window.frames["content"].location.href = 'index_verification_tools.php?action=prices';
			});

			$("#prices_global").click(function() {
				if (confirm('Are you sure?'))
					parent.window.frames["content"].location.href = 'verification/stock_setall_global_price.php';
			});

			$("#stock").click(function() {
				parent.window.frames["content"].location.href = 'index_verification_tools.php?action=stock';
			});


			$("#fs").click(function() {
				parent.window.frames["content"].location.href = 'index_verification_tools.php?action=fs';
			});
			

			$("#fs_duplicate").click(function() {
				parent.window.frames["content"].location.href = 'verification/fs_duplicate_transfers.php';
			});

			$("#fs_verify").click(function() {
				parent.window.frames["content"].location.href = 'verification/fs_verify_transactions.php';
			});

			$("#fs_transfers").click(function() {
				alert('function not available');
				//parent.window.frames["content"].location.href = 'verification/fs_verify_web_transfers.php?day='+$("#oSelectDay").val;
			});

			$("#closing_balance").click(function() {
				alert('temporarily not available');
				//parent.window.frames["content"].location.href = 'verification/stock_closing_balance.php';
			});

			$("#reset_sprice").click(function() {
				var supplier = $('#reset_supplier').val();
				if (confirm('This operation CAN NOT be undone - are you sure?'))
					parent.window.frames["content"].location.href = 'verification/supplier_update_sprice.php?supplier='+supplier;
			});

			$("#reset_zero").click(function() {
				var supplier = $('#reset_supplier').val();
				if (confirm('This operation CAN NOT be undone - are you sure?'))
					parent.window.frames["content"].location.href = 'verification/stock_reset_zero.php?supplier='+supplier;
			});

			$("#move_stock").click(function(e) {
				e.preventDefault();
				var storeroom = $('#move_storeroom').val();
				if (confirm('This operation CAN NOT be undone - are you sure?'))
					parent.window.frames["content"].location.href = 'verification/stock_move_to_storeroom.php?storeroom='+storeroom;
			});


		});

    </script>




</body>

</html>