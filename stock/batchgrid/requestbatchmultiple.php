<?
/**
* 
* @version 	$Id: requestbatch.php,v 1.1.1.1 2006/02/14 05:03:59 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Product Edit
* @name  	viewstock.php
* 
* This file uses the pear QuickForm components to display a dialog
* which lets the user create transfers
* 
* Get Parameters: 
* $_GET[id]		The batch_id to load if you want to edit a product.  Otherwise
*			the insert product page will be shown
* Variables:
* $form 			The HTML QuickForm instance
* $bool_is_cur_month		True when active month is the current calendar month
* $bool_can_modify_record	True if user showing this page has modify rights
*/

//  error_reporting(E_ERROR|E_WARNING);
  require_once("../../include/const.inc.php");
  require_once($str_application_path."include/session.inc.php");
 
//  require_once("../../include/db.inc.php");
//  require_once("../../common/functions.inc.php");

//  ini_set("include_path", '/usr/share/pear/' . PATH_SEPARATOR . ini_get("include_path"));

  require_once 'HTML/QuickForm.php';

?>
<html><head><TITLE>Request Transfer</TITLE></head><body>
<?
  $form =& new HTML_QuickForm('frmTest', 'post');
//
//  check permissions
//
  $bool_is_cur_month = $_SESSION["int_month_loaded"]==Date("m",time());
  $bool_can_modify_record = false;

  $bool_can_modify_record = (getModuleAccessLevel('Stock')>1);

  if ($_SESSION["int_user_type"]>1) {	
	$bool_can_modify_record = true;
  } 
  
  $qry_batch = new Query("SELECT
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
	sp.deleted,
	st.tax_description,
	ssp.stock_current,
	ssp.stock_reserved,
	ssp.stock_ordered,
	ssp.stock_adjusted,
	ssp.stock_minimum,
	ssp.sale_price,
	ssp.point_price,
	ssp.use_batch_price,
	ss.supplier_name
FROM 
	stock_product sp
INNER JOIN 
	stock_measurement_unit mu
ON 
	sp.measurement_unit_id=mu.measurement_unit_id
INNER JOIN ".Monthalize('stock_storeroom_product')." ssp
ON 
	ssp.product_id = sp.product_id
LEFT JOIN 
	".Monthalize('stock_tax')." st
ON 
	st.tax_id=sp.tax_id
	
INNER JOIN 
	stock_category sc
ON 	sc.category_id = sp.category_id
LEFT JOIN
      stock_supplier ss
ON ss.supplier_id = sp.supplier_id

WHERE 
storeroom_id = ".$_SESSION['int_current_storeroom']."
and (stock_current-stock_reserved+stock_ordered < stock_minimum)
limit 0,50");


  if ($qry_batch->b_error) {
	die('Nothing to request.');
  } 

  $qry_storeroom=new Query("select description from stock_storeroom where storeroom_id=".$_SESSION['int_current_storeroom']);

  $form->addElement('header', '', 'Request Internal Transfer To '.$qry_storeroom->FieldByName('description')); 
  
  $qry_storeroom->Query("select * from stock_storeroom where storeroom_id<>".$_SESSION['int_current_storeroom']);
//  $arr_storeroom_list[0] = '[no storeroom]';
  for ($i=0;$i<$qry_storeroom->RowCount();$i++) {
    		$arr_storeroom_list[$qry_storeroom->FieldByName('storeroom_id')] =  $qry_storeroom->FieldByName('description');
		$qry_storeroom->Next();
  }
  
  $storeroom_select =& $form->addElement('select', 'storeroom_id', 'From Storeroom:', $arr_storeroom_list);
  
  $form->addElement('static', '', 'Products with low stock: '); 
  
  for ($i=0;$i<$qry_batch->RowCount();$i++) {
  

    $arr_elements[]=$form->addElement('checkbox', 'product'.$qry_batch->FieldByName('product_id'), $qry_batch->FieldByName('product_description'), '');
    $arr_elements[]=$form->addElement('text', 'qty'.$qry_batch->FieldByName('product_id'), 'Quantity:');
    
    $arr_defaults['qty'.$qry_batch->FieldByName('product_id')]=$qry_batch->FieldByName('stock_minimum')-($qry_batch->FieldByName('stock_current')-$qry_batch->FieldByName('stock_reserved')+$qry_batch->FieldByName('stock_ordered'));
    
    $qry_batch->Next();
  }  
  $form->addElement('hidden', 'action', 'process');


//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules



//  $form->addRule('minimum_qty', 'Minimum quantity is not valid', 'numeric', null, 'client');






//  $form->registerRule('check_stock','function','fn_check_stock'); 
//  $form->addRule('transfer_quantity','This amount can not be requested from this storeroom.  Either the product does not exist in that storeroom, or the quantity is invalid!','check_stock'); 

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


	$form->setDefaults($arr_defaults);

//=================== load variables
//
// Fills with some defaults values
//
/*  if (!empty($_GET['id'])) {
	 $sql = new Query("
			SELECT
				*
			FROM
				".Monthalize("stock_storeroom_product")."
			WHERE
				product_id=$id");

	$form->setDefaults(array(
		'minimum_qty'  => ($sql->FieldByName("stock_minimum")+0)

	));


  }  else {
	$form->setDefaults(array(
		'is_available'  => 'Y',
		'is_perishable'  => 'N',
		'is_consolidated'  => 'Y',
		'is_av_product'  => 'N',
		'minimum_qty'=>'0'

	));

  } */



  if (!empty($_REQUEST["action"]))  {
  	if ($form->validate()) {
    // Form is validated, then processes the data
		$form->freeze();
	    	$form->process('saveForm', false);
	    	echo "\n<HR>$msg\n";
 	}
  
 }  
// Process callback

$form->display();

/**
 * Save form after all processing is done
 *
 * @param   array    array of all form variables and their values
 *
 */
function saveForm($values) {
   global $msg;
    $msg = "";
    if ($msg=="") {
    	$confirm = "";
    	// for a new record
	$parentRefresh = false;
	
	$qry_exists= new Query("BEGIN");
	
	$parentRefresh = true;
	$arr_insert = array();
	foreach ($values as $key=>$value) {
	   if (substr($key,0,7)=='product') {
	     if ($value<>'') {
	       $id = substr($key,7);
	       $arr_insert[$id]=$values['qty'.$id];
	     }
	   }
	}
	
	foreach ($arr_insert as $id=>$qty) {
  	$msg = updateStoreroomProduct($_SESSION['int_current_storeroom'],$id,0,0,$qty);	

  
    $str_update = "INSERT INTO ".Monthalize('stock_transfer')."(
        transfer_quantity,
        transfer_description,
        date_created,
        module_id,
        user_id,
        storeroom_id_from,
        storeroom_id_to,
        product_id,
        batch_id,
        transfer_type,
        transfer_status
        )
        VALUES (
        ".$qty.",
        '".addslashes('Automatic stock request')."',
        '".Date("Y-m-d h:i:s",time())."',
        1,
        ".$_SESSION['int_user_id'].",
        ".$values['storeroom_id'].",
        ".$_SESSION['int_current_storeroom'].",
        ".$id.",
        0,
        ".TYPE_INTERNAL.",
        ".STATUS_REQUESTED."
        )";
//	echo $str_update;
      $qry_exists->Query($str_update);
  
      if ($qry_exists->b_error==true) $msg="Error updating";
    }  
    
  	if (($msg=="") && ($confirm=="")) {
  		$qry_exists->Query("COMMIT");
    		?><html><body><script language="JavaScript"><? 
		if ($parentRefresh) {
      			echo ("window.opener.document.location=window.opener.document.location.href;");
    		}
		?>
    		window.close(); </script></body></html>
    		<?
    		exit;
    	} else {
	 	   $qry_exists->Query("ROLLBACK");
	     }
    }
   
}  
?>
</body>
</html>
