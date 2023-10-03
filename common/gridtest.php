<html>
<head><TITLE></TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css"></head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<script language='javascript'>
var myField='';
function gridClick(aKeyField) {
	if (aKeyField != "") {
		myField=aKeyField;
	}
}
function editClick() {
	if (myField == '') {
		alert('Please select an item to edit!');
	} else { alert(myField); }
}

function addClick() {
	alert('Adding an item');
}

</script>


<?
require_once "../include/db.inc.php";
require_once "../include/grid.inc.php";

$grid = new Grid();

$grid->addColumn("Product Code", "product_code", "string", true,100); 
$grid->addColumn("Product Name", "product_description", "string", true); 
$grid->addColumn("Category Description", "category_description", "string", true); 
$grid->addColumn("Tax", "tax_description", "string", true); 

$grid->setQuery("SELECT
	sp.product_id,
	sp.product_code,
	sp.product_description,
	sp.is_available,
	sp.is_av_product,
	sp.minimum_qty,
	sp.is_minimum_consolidated,
	sp.tax_id,
	sp.is_perishable,
	sp.measurement_unit_id,
	sp.category_id,
	mu.measurement_unit,
	sc.category_description,
	st.tax_description
	
FROM 
	stock_product sp
INNER JOIN 
	stock_measurement_unit mu
ON 
	sp.measurement_unit_id=mu.measurement_unit_id
LEFT JOIN 
	stock_tax st
ON 
	st.tax_id=sp.tax_id
	
INNER JOIN 
	stock_category sc
ON 	sc.category_id = sp.category_id");


$grid->setOnClick('gridClick','product_id');

$grid->processParameters($_GET);

//$grid->addOrder("category_description","");


//$grid->addFilter("product_code","starts with","3","string");
//$grid->addFilter("category_description","contains","cleaning","string");

$grid_form = new GridForm();

$grid_form->setGrid($grid);
$grid_form->addButton('Monica','','addClick','right');
//$grid_form->addHTML('<b>hello, world!</b>','left');
$grid_form->addButton('Edit','','editClick','left');

$grid_form->addControl('advfilter0','center');
$grid_form->addControl('filter1','right');
//$grid_form->addControl('nav','right');
//$grid_form->addControl('pagesize','center');
//$grid_form->addControl('refresh','right');
$grid_form->draw();

?>
</body></html>
