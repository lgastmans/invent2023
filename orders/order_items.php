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
    $grid->addColumn("Ordered", "quantity_ordered", "custom", true, 50, 'getQty');
    $grid->addColumn("Delivered", "quantity_delivered", "custom", true, 50, 'getQty');
    $grid->addColumn("Adjusted", "adjusted", "custom", false, 50, 'getQty');

    $grid->setQuery("SELECT
            oi.order_item_id,
            oi.quantity_ordered,
            oi.quantity_delivered,
            oi.adjusted,
            sp.product_code,
            sp.product_description
        FROM
            ".Monthalize('order_items')." oi
            INNER JOIN stock_product sp ON (sp.product_id = oi.product_id)
    ");

    $grid->processParameters($_GET);
    $grid->addUniqueFilter('oi.order_id', 'equals', $_GET["id"], 'number');
    $grid->addUniqueFilter('sp.deleted','equals','N','string');

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