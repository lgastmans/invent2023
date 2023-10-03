<?

  require_once("../../include/const.inc.php");
  require_once("../../include/session.inc.php");
  require_once("../../include/db.inc.php");
  require_once "../../include/grid.inc.php";
  
      //==================
      // get user settings
      //------------------
      $qry_settings = new Query("
	    SELECT stock_show_available, bill_decimal_places
	    FROM user_settings
	    WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
      ");
      $str_show_available = 'Y';
      $int_decimal_places = 2;
      if ($qry_settings->RowCount() > 0) {
	    $str_show_available = $qry_settings->FieldByName('stock_show_available');
	    $int_decimal_places = $qry_settings->FieldByName('bill_decimal_places');
      }
      
function dispQty($f_field, $f_qry) {
      global $int_decimal_places;

      if ($f_qry->FieldByName('is_decimal') == 'Y') {
	    if ($int_decimal_places == 2)
		  echo sprintf("%0.2f",$f_qry->FieldByName($f_field));
	    else
		  echo sprintf("%0.3f",$f_qry->FieldByName($f_field));
      }
      else
	    echo sprintf("%0.0f",$f_qry->FieldByName($f_field));
}

$grid = new Grid();
$grid->addColumn("Code", "product_code", "string", true, 50);
$grid->addColumn("Description", "product_description", "string", true, 100);
$grid->addColumn("Qty", "quantity", "custom", true, 50, 'dispQty');
$grid->addColumn("Unit", "measurement_unit", "string", false, 50);
$grid->addColumn("Price", "price", "number", true, 50);
$grid->addColumn("Batch", "batch_code", "string", true, 50);

$grid->setQuery("
SELECT
	sri.rts_item_id,
	sri.quantity,
	sri.price,
	sp.product_code,
	sp.product_description,
	sb.batch_code,
        mu.measurement_unit,
        mu.is_decimal
FROM
	".Monthalize('stock_rts_items')." sri
	INNER JOIN stock_product sp ON (sp.product_id = sri.product_id)
	INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = sri.batch_id)
        INNER JOIN stock_measurement_unit mu ON sp.measurement_unit_id=mu.measurement_unit_id
");

$grid->processParameters($_GET);
$grid->addUniqueFilter('sri.rts_id', 'equals', $_GET["id"], 'number');

?>
<html>
<head><TITLE></TITLE>
<link href="../../include/styles.css" rel="stylesheet" type="text/css">

<script language='javascript'>


function doResize() {
	parent.parent.frames["rts_content"].doResize(2);
}
</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<?
$grid_form = new GridForm();

$grid_form->setGrid($grid);
$grid_form->str_stylesheet='../../include/styles.css';
$grid->str_image_path='../../images/';
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../../images/resize.gif','doResize','right');
$grid_form->setFrames('items_menu','items_content');
$grid_form->draw();

?>

</body>
</html>