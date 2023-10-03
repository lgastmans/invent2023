<?php
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");

	$error=false;


	if (isset($_POST['action'])) {

		$boundary = intval($_POST['priceBoundary']);
		$upperTax = $_POST['upperTax'];
		$lowerTax = $_POST['lowerTax'];

		if (empty($boundary)) {
			$error = true;
			$errmsg =  "Boundary value cannot be zero";
		}
		if ($upperTax == $lowerTax) {
			$error = true;
			$errmsg = "Taxes cannot be the same";
		}


		if (!$error) {

			$sql = "
					SELECT ssp.*, sp.product_code, sp.product_description
					FROM ".Monthalize("stock_storeroom_product")." ssp
					INNER JOIN stock_product sp ON (sp.product_id = ssp.product_id)
					INNER JOIN stock_category sc ON (sc.category_id = sp.category_id)
					WHERE ssp.storeroom_id = ".$_SESSION['int_current_storeroom']."
						AND sc.category_id = ".$_POST['priceCategory'];
			$qry = $conn->query($sql);


			$data=array();

			while ($obj = $qry->fetch_object()) {

				$data[$i]["id"] = $obj->product_id;
				$data[$i]["code"] = $obj->product_code;
				$data[$i]["description"] = $obj->product_description;
				$data[$i]["use_batch_price"] = $obj->use_batch_price;

				if ($obj->use_batch_price=='N') {

					$data[$i]['selling_price'] = $obj->sale_price;

					if ($obj->sale_price >= $boundary) {

						$update = $conn->query("
							UPDATE ".Yearalize('stock_batch')."
							SET tax_id = ".$upperTax."
							WHERE product_id = ".$obj->product_id."
								AND (is_active = 'Y')
						");

					}
					else {

						$update = $conn->query("
							UPDATE ".Yearalize('stock_batch')."
							SET tax_id = ".$lowerTax."
							WHERE product_id = ".$obj->product_id."
								AND (is_active = 'Y')
						");
						
					}

				}
				else {

					/*
						stock_batch table
					*/
					
					$qry2 = $conn->query("
						SELECT sb.batch_id, sb.batch_code, sb.buying_price, sb.selling_price, sb.tax_id
						FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
						WHERE (sb.product_id = ".$obj->product_id.") AND
							(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
							(sb.status = ".STATUS_COMPLETED.") AND
							(sb.deleted = 'N') AND
							(ssb.product_id = sb.product_id) AND
							(ssb.batch_id = sb.batch_id) AND
							(ssb.storeroom_id = sb.storeroom_id) AND 
							(ssb.is_active = 'Y')
						ORDER BY date_created
					");
					
					while ($obj2 = $qry2->fetch_object()) {

						if ($obj2->selling_price >= $boundary) {

							$update = $conn->query("
								UPDATE ".Yearalize('stock_batch')."
								SET tax_id = ".$upperTax."
								WHERE batch_id = ".$obj2->batch_id."
									AND (is_active = 'Y')
							");

						}
						else {

							$update = $conn->query("
								UPDATE ".Yearalize('stock_batch')."
								SET tax_id = ".$lowerTax."
								WHERE product_id = ".$obj2->batch_id."
									AND (is_active = 'Y')
							");
							
						}

					}

				}

			} // while

		} // error

	}


	// list of taxes
	$qry_tax = $conn->query("
		SELECT tax_id, tax_description
		FROM ".Monthalize('stock_tax')."
		ORDER BY tax_description"
	);

	$arr_tax = array();
	while ($obj = $qry_tax->fetch_object()) {
		$arr_tax[$obj->tax_id] = $obj->tax_description;
	}

	// list of categories
	$qry_categories = $conn->query("
		SElECT *
		FROM stock_category
		ORDER BY category_description
	");
	$arr_categories = array();
	while ($obj = $qry_categories->fetch_object()) {
		$arr_categories[$obj->category_id] = $obj->category_description;
	}


?>
<html>

<head>
	<link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		body {
			margin:25px;
		}
	</style>
</head>

<body>

	<div class="container">

		<form id="target" method="post" >

				<div id="settings_alert" class="alert alert-danger alert-dismissible" role="alert" style="display:<?php echo (!$error) ? 'none' : 'inline'; ?>;">
					<?php echo $errmsg;?>
				</div>
				<br><br>
		  
			<div class="form-group">
				<label for="priceBoundary">Price Boundary</label>
				<input type="text" class="form-control" name="priceBoundary" id="priceBoundary" placeholder="1000">
			</div>

			<div class="form-group">
				<label for="lowerTax">Lower Boundary Tax:</label>
				<select class="form-control" name="lowerTax" id="lowerTax">
					<?php 
						foreach ($arr_tax as $key=>$val) {
							echo "<option value='".$key."'>".$val."</option>";
						}
					?>
				</select>
			</div> 

			<div class="form-group">
				<label for="upperTax">Upper Boundary Tax:</label>
				<select class="form-control" name="upperTax" id="upperTax">
					<?php 
						foreach ($arr_tax as $key=>$val) {
							echo "<option value='".$key."'>".$val."</option>";
						}
					?>
				</select>
			</div> 

			<div class="form-group">
				<label for="priceCategory">Category:</label>
				<select class="form-control" name="priceCategory" id="priceCategory">
					<?php 
						foreach ($arr_categories as $key=>$val) {
							echo "<option value='".$key."'>".$val."</option>";
						}
					?>
				</select>
			</div>

			<button type="submit" value="general" name="action" id="btn-general" class="btn btn-primary">Save</button>


			<div class="well well-sm">Note: Only the tax for existing stock will be changed. The tax setting in Admin > Products will not be changed.</div>

		</form>

	</div>

<script src="../include/js/jquery-3.2.1.min.js"></script>

<script>

    $( document ).ready(function() {

    });

</script>


</body>
</html>