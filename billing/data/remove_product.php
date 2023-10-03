<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");

	if (isset($_POST['del'])) {

		for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {

			if ($_SESSION["arr_total_qty"][$i][0] === $_SESSION['current_code']) {

				$_SESSION["arr_total_qty"] = array_delete($_SESSION["arr_total_qty"], $i);

				remove_product();

			}
		}
	}

	echo json_encode("product removed");

?>