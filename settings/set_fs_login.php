<?php
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");
	


	/*
		user details
	*/
	$qry = $conn->query("
		SELECT fs_user, fs_password
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']
	);
	$obj = $qry->fetch_object();


	/*
		save form
	*/

	$error = false;

	if (isset($_POST['action'])) {

		$can_save = true;

		$fs_password = $_POST['fs_password'];
		$fs_confirm = $_POST['fs_confirm'];
		
		if ($fs_password === $fs_confirm) {

		    $sql = "
				UPDATE user_settings
				SET
					fs_password	= '".base64_encode($fs_password)."'
				WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];


			$qry = new Query($sql);

			if ($qry->b_error == true) {
				$error = true;
				$msg = "An error occurred - ".$qry->err;
			}

		}
		else {
			$error = true;
			$msg = "Passwords do not match!";
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

			<div id="settings_alert" class="alert alert-danger alert-dismissible" role="alert" style="display: visible;">
				This password needs to correspond to the password at FS Online !<br>
				Resetting the password here will not update it at FS Online.
			</div>

			<div id="settings_alert" class="alert alert-danger alert-dismissible" role="alert" style="display:<?php echo (!$error) ? 'none' : 'visible'; ?>;">
				<?php echo $msg;?>
			</div>
			<br><br>


			<!-- FS User -->
			<div class="form-group">
				<label for="priceBoundary">FS User</label>
				<input type="text" class="form-control" name="fs_user" id="fs_user" readonly placeholder="FS User" value="<?php echo base64_decode($obj->fs_user); ?>">
			</div>


			<!-- FS Password -->
			<div class="form-group">
				<label for="priceBoundary">FS Password</label>
				<input type="password" class="form-control" name="fs_password" id="fs_password" placeholder="FS Password" value="">
			</div>

			<!-- FS Password Confirm -->
			<div class="form-group">
				<label for="priceBoundary">Confirm FS Password</label>
				<input type="password" class="form-control" name="fs_confirm" id="fs_confirm" placeholder="Confirm FS Password" value="">
			</div>


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