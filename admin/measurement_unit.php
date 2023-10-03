<?php
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");


	/*
		retrieve details
	*/
	$id = false;
	if (IsSet($_GET['id'])) 
		$id = $_GET['id']; 


	$measurement_unit = '';
	$is_decimal = 'Y';

	if ($id) {

		$sql = "
			SELECT *
			FROM stock_measurement_unit
			WHERE measurement_unit_id = $id";

		$qry = new Query($sql);

		$measurement_unit = $qry->FieldByName('measurement_unit');
		$is_decimal = $qry->FieldByName('is_decimal');

	}



	/*
		save form
	*/

	$error = false;

	if (isset($_POST['action'])) {

		$can_save = true;

		if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		      $msg = 'Updated successfully';
		}
		else {
		      $msg = 'Cannot update in previous months. <br> Select the current month/year and continue.';
		      $can_save = false;
		}

		if ($can_save) {


			//print_r($_POST);
			//$error=true;

			$is_decimal = 'N';
			if (isset($_POST['is_decimal']))
				$is_decimal = 'Y';


			if ($_POST['id']) {

				$sql = "
					UPDATE stock_measurement_unit
					SET 
						measurement_unit 	= '".$_POST['measurement_unit']."',
						is_decimal			= '".$is_decimal."',
						is_modified			= 'Y'
					WHERE measurement_unit_id = ".$_POST['id'];

			}
			else {
				$sql = "
					INSERT INTO stock_measurement_unit
						(measurement_unit,
						is_decimal,
						is_modified
						) 
					VALUES (
						'".$_POST['measurement_unit']."',
						'".$is_decimal."',
						'Y'
					)";

			}

			$qry = new Query($sql);

			if ($qry->b_error == true) {

				$error = true;
				$msg = "There was an error trying to save the information! ".$qry->err;

			}			

		}
		else {

			$error = true;

		}
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


			<div id="settings_alert" class="alert alert-danger alert-dismissible" role="alert" style="display:<?php echo (!$error) ? 'none' : 'visible'; ?>;">
				<?php echo $msg;?>
			</div>
			<br><br>


			<!-- Batch Code -->
			<div class="form-group">
				<label for="measurement_unit">Measurement Unit</label>
				<input type="text" class="form-control" name="measurement_unit" id="measurement_unit" placeholder="Unit" value="<?php echo $measurement_unit; ?>">
			</div>


			<!-- Active -->
			<div class="checkbox">
			  <label>
			    <input type="checkbox" name="is_decimal" id="is_decimal" value="" <?php echo ($is_decimal=='Y' ? "checked" : "");?> >
			    Decimal
			  </label>
			</div>


			<input type="hidden" name="id" id="id" value="<?php echo $id; ?>" > 


			<button type="submit" value="general" name="action" id="btn-general" class="btn btn-primary">Save</button>


		</form>

	</div>

<script src="../include/js/jquery-3.2.1.min.js"></script>
<script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
<script src="../include/js/bootbox.min.js"></script>

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