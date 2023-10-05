<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

  $qry_products = new Query("
    SELECT product_id
    FROM stock_product
    ORDER BY product_id
  ");
  
  $qry_batch = new Query("
    SELECT *
    FROM ".Monthalize('stock_storeroom_batch')."
  ");
  $qry_update = new Query("
    SELECT *
    FROM ".Monthalize('stock_storeroom_batch')."
  ");
?>

<html>
<body>
<?
  echo "checking ".$qry_products->RowCount()." products.<br>"
?>

<? 
  for ($i=0;$i<$qry_products->RowCount();$i++) {
  
    $qry_batch->Query("
      SELECT ssb.stock_storeroom_batch_id, ssb.is_active 
      FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
      WHERE (sb.product_id = ".$qry_products->FieldByName('product_id').") AND
        (sb.storeroom_id = 1) AND
        (sb.is_active = 'Y') AND
        (sb.status = 3) AND
        (sb.deleted = 'N') AND
        (ssb.product_id = sb.product_id) AND
        (ssb.batch_id = sb.batch_id) AND
        (ssb.storeroom_id = sb.storeroom_id)
      ORDER BY date_created DESC
    ");
    
    if ($qry_batch->FieldByName('is_active') == 'N') {
      echo "-- updated ".$qry_batch->FieldByName('stock_storeroom_batch_id')."<br>";
      $qry_update->Query("
        UPDATE ".Monthalize('stock_storeroom_batch')."
        SET is_active = 'Y'
        WHERE stock_storeroom_batch_id = ".$qry_batch->FieldByName('stock_storeroom_batch_id')."
      ");
    }
	
    $qry_products->Next();
  }
?>

done
</body>
</html>