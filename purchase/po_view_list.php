<?
/**
*
* @version 	$Id: po_view_list.php,v 1.2 2006/02/15 06:56:17 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		23 Nov 2005
* @module 	Purchase Order View - List frame
* @name  	po_view_list.php
*
* The List frame is part of the purchase_view_frameset.
* It is the frame that contains the list of items for the purchase order
*
*/
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once "../include/grid.inc.php";

	if ($_SESSION['str_user_color_scheme'] == 'standard')
		$str_css_filename = 'bill_styles.css';
	else if ($_SESSION['str_user_color_scheme'] == 'blue')
		$str_css_filename = 'bill_styles_blue.css';
	else if ($_SESSION['str_user_color_scheme'] == 'purple')
		$str_css_filename = 'bill_styles_purple.css';
	else if ($_SESSION['str_user_color_scheme'] == 'green')
		$str_css_filename = 'bill_styles_green.css';
	else
		$str_css_filename = 'bill_styles.css';

?>

<html>
<head><TITLE></TITLE>
<link href="../include/<?echo $str_css_filename?>" rel="stylesheet" type="text/css">
</head>

<body>

<?
	if (!IsSet($_GET["id"]))
		$current_id=0;
	else
		$current_id=$_GET["id"];

$grid = new Grid();

$grid->addColumn("Code", "product_code", "string", true, 100);
$grid->addColumn("Description", "product_description", "string", true);
$grid->addColumn("Ordered", "quantity_ordered", "number", true);


$grid->setQuery("SELECT
		sp.product_code,
		sp.product_description,
		sp.quantity_per_box,
		pi.quantity_ordered,
		pi.purchase_order_id
	FROM
		stock_product sp
	INNER JOIN ".Yearalize('purchase_items')." pi ON (sp.product_id = pi.product_id)");


$grid->processParameters($_GET);
$grid->addUniqueFilter('pi.purchase_order_id', 'equals', $current_id, 'number');
$grid->str_header_class = 'columnheader';
$grid_form = new GridForm();

$grid_form->setGrid($grid);
$grid_form->draw();
?>

</body>
</html>