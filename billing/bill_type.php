<?php
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");

	$error=false;


	if (isset($_POST['action'])) {
    
    	$can_save = true;

	    if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
	          $errmsg = 'Updated successfully';
	    }
	    else {
	          $errmsg = 'Cannot update in previous months. <br> Select the current month/year and continue.';
	          $can_save = false;
	    }

    	if ($can_save) {

    		$bill_id = $_POST['bill_id'];
			$currentType = $_POST['payment_type'];
			$newType = $_POST['newType'];

			if ($currentType == $newType) {
				$error = true;
				$errmsg = "Please select another bill type";
			}
			if (!isset($newType)) {
				$error = true;
				$errmsg = "Unknown bill type";
			}

			if (!$error) {

				$sql = "
					UPDATE ".Monthalize('bill')."
					SET payment_type = $newType
					WHERE (bill_id = $bill_id)
				";

				$qry = $conn->query($sql);

				if ($qry->b_error == true) {
					$error = true;
					$errmsg = "There was an error trying to save the information! ".$qry->err;
				} 

			} // error
		} // can save
	}

	$bill_id = 0;
	if (isset($_GET['bill_id'])) {
		$bill_id = $_GET['bill_id'];

		$sql = "
			SELECT bill_id, bill_number, payment_type
			FROM ".Monthalize("bill")." b
			WHERE (bill_id = $bill_id)";

		$qry = $conn->query($sql);

		$obj = $qry->fetch_object();

		if ($obj) {
			if ($obj->payment_type == 1)
				$payment_type = "Cash";
			elseif  ($obj->payment_type == 2)
				$payment_type = "FS Account";
			elseif ($obj->payment_type == 4)
				$payment_type = "Credit Card";
			elseif ($obj->payment_type == 5)
				$payment_type = "Cheque";
			elseif ($obj->payment_type == 7)
				$payment_type = "Aurocard";
			elseif ($obj->payment_type == 8)
				$payment_type = "UPI";
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

			<input type="hidden" value="<?php echo $bill_id;?>" name="bill_id">
			<input type="hidden" value="<?php echo $obj->payment_type;?>" name="payment_type">

			<div id="settings_alert" class="alert alert-danger alert-dismissible" role="alert" style="display:<?php echo (!$error) ? 'none' : 'inline'; ?>;">
				<?php echo $errmsg;?>
			</div>
			<br><br>
		  
			<div class="form-group">
				<label for="currentType">Current Bill Type</label>
				<input type="text" class="form-control" name="currentType" id="current_bill_type" placeholder="<?php echo $payment_type;?>" readonly>
			</div>

			<div class="form-group">
				<label for="newType">New Bill Type:</label>
				<select class="form-control" name="newType" id="new_bill_type">
					<?php 
						echo "<option value='1'>Cash</option>";
						echo "<option value='2'>FS Account</option>";
						echo "<option value='4'>Credit Card</option>";
						echo "<option value='5'>Cheque</option>";
						echo "<option value='7'>Aurocard</option>";
						echo "<option value='8'>UPI</option>";
					?>
				</select>
			</div> 


			<button type="submit" value="general" name="action" id="btn-general" class="btn btn-primary">Save</button>


			<!-- <div class="well well-sm">Note: Only the tax for existing stock will be changed. The tax setting in Admin > Products will not be changed.</div> -->

		</form>

	</div>

<script src="../include/js/jquery-3.2.1.min.js"></script>
<script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
<script src="../include/js/bootbox.min.js"></script>

<script>

    $( document ).ready(function() {

      <?php if ((!$error) && ($can_save)) { ?>

        bootbox.alert("Saved successfully", function() {
        	if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
	        window.close();
        });

      <?php } ?>

    });

</script>


</body>
</html>