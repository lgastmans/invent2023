<?
	require_once("../include/purchase_funcs.inc.php");

	$int_po_id = new_purchase_order();

	// navigate to the editing form for the purchase orders
	echo "<script language=\"javascript\">";
	echo "document.location = 'purchase_view_frameset.php?id='+".$int_po_id;
	echo "</script>";
?>
