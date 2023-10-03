<?php
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");



	/*
		list of taxes
	*/
	$qry_tax = $conn->query("
		SELECT tax_id, tax_description
		FROM ".Monthalize('stock_tax')."
		ORDER BY tax_description"
	);

	$arr_tax = array();
	while ($obj = $qry_tax->fetch_object()) {
		$arr_tax[$obj->tax_id] = $obj->tax_description;
	}


	/*
		list of suppliers
	*/
	$qry_supplier = $conn->query("
		SELECT supplier_id, supplier_name
		FROM stock_supplier
		ORDER BY supplier_name
	");

	$arr_supplier = array();
	while ($obj = $qry_supplier->fetch_object()) {
		$arr_supplier[$obj->supplier_id] = $obj->supplier_name;
	}


	/*
		retrieve batch details
	*/
	if (IsSet($_GET['id'])) 
		$batch_id = $_GET['id']; 

	if (IsSet($_GET['batch_id'])) 
		$batch_id = $_GET['batch_id'];   

	$qry_batch = new Query("
		SELECT *, sb.is_active AS is_active, sb.supplier_id AS current_supplier_id
		FROM ".Yearalize('stock_batch')." sb
		INNER JOIN stock_product sp	ON sp.product_id=sb.product_id
		LEFT JOIN ".Monthalize('stock_tax')." st ON st.tax_id=sb.tax_id
		WHERE batch_id=".$batch_id."
			AND storeroom_id=".$_SESSION['int_current_storeroom']
	);


	/*
		save form
	*/

	$error = false;

	if (isset($_POST['action'])) {

		$can_save = true;

		if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		      $msg = 'Batch updated successfully';
		}
		else {
		      $msg = 'Cannot update batches in previous months. \\n Select the current month/year and continue.';
		      $can_save = false;
		}

		if ($can_save) {

			$is_active = 'N';
			if (isset($_POST['is_active']))
				$is_active = 'Y';

		    $sql = "
				UPDATE ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
				SET
					sb.batch_code	='".$_POST['batch_code']."',
					sb.buying_price	=".$_POST['buying_price'].",
					sb.selling_price=".$_POST['selling_price'].",
					sb.tax_id 		=".$_POST['tax_id'].",
					sb.supplier_id 	=".$_POST['supplier_id'].",
					sb.is_active 	= '".$is_active."',
					ssb.is_active 	= '".$is_active."'
				WHERE sb.batch_id=".$_POST['batch_id']."
					AND ssb.batch_id = sb.batch_id
					AND sb.storeroom_id = ".$_SESSION['int_current_storeroom'];

			$qry = new Query($sql);

			if ($qry->b_error == true) {
				$error = true;
				$msg = "There was an error while trying to save your information! ".$qry->err;
			}
		}
		else {
			$error = true;
		}
	}

?>

<html>

<head>
	<link href="../../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		body {
			margin:25px;
		}
	</style>
</head>

<body>

	<div class="container">

		<form id="target" method="post" >


			<div id="settings_alert" class="alert alert-danger alert-dismissible" role="alert" style="display:<?php echo (!$error) ? 'none' : 'visible'; ?>;">
				<?php echo $msg;?>
			</div>
			<br><br>


			<!-- Batch Code -->
			<div class="form-group">
				<label for="priceBoundary">Batch Code</label>
				<input type="text" class="form-control" name="batch_code" id="batch_code" placeholder="Batch Code" value="<?php echo $qry_batch->FieldByName("batch_code"); ?>">
			</div>

			<!-- Buying Price -->
			<div class="form-group">
				<label for="priceBoundary">Buying Price</label>
				<input type="text" class="form-control" name="buying_price" id="buying_price" placeholder="Buying Price" value="<?php echo $qry_batch->FieldByName("buying_price"); ?>">
			</div>

			<!-- Selling Price -->
			<div class="form-group">
				<label for="priceBoundary">Selling Price</label>
				<input type="text" class="form-control" name="selling_price" id="selling_price" placeholder="Selling Price" value="<?php echo $qry_batch->FieldByName("selling_price"); ?>">
			</div>

			<!-- Tax -->
			<div class="form-group">
				<label for="tax">Tax:</label>
				<select class="form-control" name="tax_id" id="tax_id">
					<?php 
						foreach ($arr_tax as $key=>$val) {
							if ($key == $qry_batch->FieldByName('tax_id'))
								echo "<option value='".$key."' selected>".$val."</option>";
							else
								echo "<option value='".$key."'>".$val."</option>";
						}
					?>
				</select>
			</div> 

			<!-- Supplier -->
			<div class="form-group">
				<label for="upperTax">Supplier:</label>
				<select class="form-control" name="supplier_id" id="supplier_id">
					<?php 
						foreach ($arr_supplier as $key=>$val) {
							if ($key == $qry_batch->FieldByName('current_supplier_id'))
								echo "<option value='".$key."' selected>".$val."</option>";
							else
								echo "<option value='".$key."'>".$val."</option>";
						}
					?>
				</select>
			</div> 

			<!-- Active -->
			<div class="checkbox">
			  <label>
			    <input type="checkbox" name="is_active" id="is_active" value="" <?php echo ($qry_batch->FieldByName("is_active")=='Y' ? "checked" : "");?> >
			    Active
			  </label>
			</div>


			<input type="hidden" name="batch_id" id="batch_id" value="<?php echo $batch_id; ?>" > 


			<button type="submit" value="general" name="action" id="btn-general" class="btn btn-primary">Save</button>


		</form>

	</div>

<script src="../../include/js/jquery-3.2.1.min.js"></script>
<script src="../../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
<script src="../../include/js/bootbox.min.js"></script>

<script>

    $( document ).ready(function() {

    	<?php if ((!$error) && ($can_save)) { ?>

				bootbox.alert("Saved successfully", function() { 

					window.close();

				});

    	<?php } ?>

    });

</script>


</body>
</html>			