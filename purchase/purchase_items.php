<?

  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once "../include/grid.inc.php";

$grid = new Grid();
$grid->addColumn("Code", "product_code", "string", true, 100);
$grid->addColumn("Description", "product_description", true, 250);
$grid->addColumn("Ordered", "quantity_ordered", true);
$grid->addColumn("Received", "quantity_received", true);
$grid->addColumn("Bonus", "quantity_bonus", true);
$grid->addColumn("Buying Price", "buying_price", "currency", true);
$grid->addColumn("Selling Price", "selling_price", "currency", true);
$grid->addColumn("Supplier", "supplier_name", "string", true);

$grid->setQuery("SELECT
	pi.purchase_item_id,
	sp.product_code,
	sp.product_description,
	pi.quantity_ordered,
	pi.quantity_received,
	pi.quantity_bonus,
	pi.buying_price,
	pi.selling_price,
	sup.supplier_name
FROM
	".Yearalize('purchase_items')." pi
INNER JOIN
	stock_product sp ON (pi.product_id=sp.product_id)
LEFT JOIN
	stock_supplier sup ON (pi.supplier_id = sup.supplier_id)");

$grid->processParameters($_GET);
$grid->addUniqueFilter('pi.purchase_order_id', 'equals', $_GET["id"], 'number');
//$grid->addUniqueFilter('sp.deleted','equals','N','string');

?>
<html>
<head><TITLE></TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">

<script language='javascript'>


function doResize() {
	parent.parent.frames["purchase_content"].doResize(2);
}
</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<?
$grid_form = new GridForm();

$grid_form->setGrid($grid);
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize','right');
$grid_form->setFrames('items_menu','items_content');
$grid_form->draw();

?>

</body>
</html>