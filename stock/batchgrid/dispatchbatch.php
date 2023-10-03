<?
/**
* 
* @version 	$Id: dispatchbatch.php,v 1.1.1.1 2006/02/14 05:03:59 cvs Exp $
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
<html><head><TITLE>Dispatch Internal Transfer</TITLE></head><body>
<?
  $form =& new HTML_QuickForm('frmTest', 'get');
//
//  check permissions
//
  $bool_is_cur_month = $_SESSION["int_month_loaded"]==Date("m",time());
  $bool_can_modify_record = false;

  $bool_can_modify_record = (getModuleAccessLevel('Stock')>1);

  if ($_SESSION["int_user_type"]>1) {	
	$bool_can_modify_record = true;
  } 

 if (!$bool_can_modify_record) die("No permission to dispatch transfers.");

  // only use requested stuff

  $qry_transfer = new Query("SELECT * 
			FROM 
				".Monthalize("stock_transfer")."
			WHERE
				transfer_id=".$_GET["id"]."
			AND	transfer_status=".STATUS_REQUESTED."
			AND	storeroom_id_from=".$_SESSION['int_current_storeroom']."
			");

  if ($qry_transfer->RowCount()<1) die("You do not have permission to dispatch this transfer.");

  $num_qty_available = $qry_transfer->FieldByName('transfer_quantity');

  if (empty($_GET['product_id'])) {
  	$int_product_id = $qry_transfer->FieldByName('product_id');
  } else 
	$int_product_id=$_GET['product_id'];

  if (empty($_GET['batch_id'])) {
  	$int_batch_id = 0;
  } else 
	$int_batch_id=$_GET['batch_id'];
  
  $qry_batch = new Query("SELECT
				ssb.batch_id,
				ssb.product_id,
				sp.product_description,
				ssb.storeroom_id,
				ssb.stock_available,
				ssb.stock_reserved,
				sb.batch_code
			  FROM ".Monthalize('stock_storeroom_batch')." ssb

			  INNER JOIN
				stock_product sp
			  ON 
				sp.product_id = ssb.product_id

			  INNER JOIN
				".Yearalize('stock_batch')." sb
			  ON 
				sb.batch_id = ssb.batch_id

			  WHERE
				ssb.product_id=".$int_product_id." 
			  AND 
				ssb.storeroom_id=".$_SESSION['int_current_storeroom']);

  if ($qry_batch->b_error) {
	die('Error getting batch details.');
  }

  $qry_storeroom=new Query("select description from stock_storeroom where storeroom_id=".$_SESSION['int_current_storeroom']);


  $form->addElement('header', '', 'Dispatch Internal Transfer From '.$qry_storeroom->FieldByName('description')); 
  
  $qry_storeroom->Query("select description from stock_storeroom where storeroom_id=". $_SESSION['int_current_storeroom']);
  $form->addElement('static', '', 'To: '.$qry_storeroom->FieldByName('description')); 

  $form->addElement('static', '', 'Product: '.$qry_batch->FieldByName('product_description')); 
  $form->addElement('static', '', 'Quantity: '.$qry_transfer->FieldByName('transfer_quantity')); 
  $form->addElement('static', '', 'Description: '.$qry_transfer->FieldByName('transfer_description')); 
  $form->addElement('hidden', 'id', $_GET['id']);
  $form->addElement('hidden', 'transfer_quantity', $qry_transfer->FieldByName('transfer_quantity'));
  $form->addElement('hidden', 'product_id', $int_product_id);
  $form->addElement('hidden', 'action', 'process');
//  $arr_storeroom_list[0] = '[no storeroom]';
  for ($i=0;$i<$qry_batch->RowCount();$i++) {
    		$arr_batch_list[$qry_batch->FieldByName('batch_id')] =  $qry_batch->FieldByName('batch_code'). " (".
			($qry_batch->FieldByName('stock_available')-$qry_batch->FieldByName('stock_reserved'))." available)";
		$qry_batch->Next();
  }
  $batch_select =& $form->addElement('select', 'batch_id', 'Select Batch:', $arr_batch_list);


//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules



//  $form->addRule('minimum_qty', 'Minimum quantity is not valid', 'numeric', null, 'client');



/**
 * Check for duplicate product code before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */
function fn_check_stock($element_name, $element_value) {
	global $qry_transfer;
//	return false;
	$qry = new Query("select * from ".Monthalize("stock_storeroom_batch")." where batch_id=".$element_value." and storeroom_id=".$_SESSION['int_current_storeroom']." and (stock_available-stock_reserved>=".$qry_transfer->FieldByName('transfer_quantity').")");
    	if ($qry->RowCount() > 0) {
		if ($element_value<=0) 
			return false;
		return true;
	}

    
   	return false;

}



  $form->registerRule('check_stock','function','fn_check_stock'); 
  $form->addRule('batch_id','Insufficient stock currently available for this batch!','check_stock'); 

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


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



  if (!empty($_GET["action"]))  {
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
   global $qry_transfer;

    $msg = "";
    if ($msg=="") {
    	$confirm = "";
    	// for a new record
	$parentRefresh = false;
	$qry_exists= new Query("BEGIN");
	$qry_exists->Query("
		SELECT 
			product_id
		FROM 
			".Monthalize('stock_storeroom_product')."
		WHERE 
			storeroom_id = ".$qry_transfer->FieldByName('storeroom_id_to')."
		AND	
			product_id=".$values['product_id']);
	
    	if ($qry_exists->FieldByName("product_id") <> $values['product_id']) {
		//echo "adding new record into storeroom";
	    	$stUpdate = "
		INSERT INTO ".Monthalize('stock_storeroom_product')." (
			product_id,
			storeroom_id
			)
			VALUES (
			".$values['product_id'].",
			".$qry_transfer->FieldByName('storeroom_id_to')."
			)
			";
			//die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			}

			$existQuery->Free();
			$parentRefresh=true;

	}
	$qry_exists->Query("
		SELECT 
			batch_id,
			product_id
		FROM 
			".Monthalize('stock_storeroom_batch')."
		WHERE 
			storeroom_id = ".$qry_transfer->FieldByName('storeroom_id_to')."
		AND	
			batch_id=".$values['batch_id']);
	
    	if ($qry_exists->FieldByName("batch_id") <> $values['batch_id']) {
//		echo "Inserting new batch";
	    	$stUpdate = "
		INSERT INTO ".Monthalize('stock_storeroom_batch')." (
			product_id,
			storeroom_id,
			batch_id
			
			) 
			VALUES (
			".$values['product_id'].",
			".$qry_transfer->FieldByName('storeroom_id_to').",
			".$values['batch_id']."
			
			)
			";
			//die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to create the batch in the new storeroom! ".$existQuery->GetErrorMessage();
			}
			
			$existQuery->Free();
			$parentRefresh=true;

			
	} 
	$parentRefresh = true;
//	echo "Updating Storeroom Batch";
	$msg = updateStoreroomBatch($_SESSION['int_current_storeroom'],$values['batch_id'],0,$qry_transfer->FieldByName('transfer_quantity'),0);
	$msg .= updateStoreroomProduct($_SESSION['int_current_storeroom'],$values['product_id'],0,$qry_transfer->FieldByName('transfer_quantity'),0);	
	$msg.=updateStoreroomBatch($qry_transfer->FieldByName('storeroom_id_to'),$values['batch_id'],0,0,$values['transfer_quantity']);

//	$msg.=updateStoreroomProduct($values['storeroom_id'],$values['product_id'],0,0,$values['transfer_quantity']);

	$str_update = "UPDATE ".Monthalize('stock_transfer')."
			SET
			batch_id=".$values['batch_id'].",
			transfer_status=".STATUS_DISPATCHED.",
			user_id_dispatched=".$_SESSION['int_user_id']."
			WHERE
			transfer_id = ".$values["id"]."
			";
//	echo $str_update;
	$qry_exists->Query($str_update);

	if ($qry_exists->b_error==true) $msg="Error updating";

//	$msg = "forced error - rollback!";	
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
