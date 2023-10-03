<?

  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once "../include/grid.inc.php";

	function getQty($f_field, $f_qry) {
		echo sprintf("%01.3f", $f_qry->FieldByName($f_field));
	}

$grid = new Grid();
$grid->addColumn("Code", "product_code", "string", true, 50);
$grid->addColumn("Description", "product_description", "string", true, 100);
$grid->addColumn("Qty", "quantity", "custom", true, 50, 'getQty');
$grid->addColumn("Adjusted", "adjusted_quantity", "custom", false, 50, 'getQty');
$grid->addColumn("Price", "price", "number", true, 50);
$grid->addColumn("Amount", "amount", "number", false, 50);
$grid->addColumn("Batch", "batch_code", "string", true, 50);
$grid->addColumn("Discount", "discount", "number", true, 50);

$grid->setQuery("SELECT
	bi.bill_item_id,
	bi.quantity,
	bi.adjusted_quantity,
	bi.discount,
	((bi.quantity + bi.adjusted_quantity) * bi.price) AS amount,
	sp.product_code,
	sp.product_description,
	sb.batch_code,
	bi.price
FROM
	".Monthalize('bill_items')." bi
	INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
	LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = bi.batch_id)
");

$grid->processParameters($_GET);
$grid->addUniqueFilter('bi.bill_id', 'equals', $_GET["id"], 'number');

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