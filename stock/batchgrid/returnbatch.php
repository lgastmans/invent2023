<?
/**
* 
* @version 	$Id: returnbatch.php,v 1.1.1.1 2006/02/14 05:03:59 cvs Exp $
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
  
  $qry_batch = new Query("SELECT
				ssb.batch_id,
				ssb.product_id,
				sp.product_description,
				ssb.storeroom_id,
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
				ssb.batch_id=".$_GET['id']." 
			  AND 
				ssb.storeroom_id=".$_SESSION['int_current_storeroom']);

  if ($qry_batch->b_error) {
	die('Error getting batch details.');
  }

  $qry_storeroom=new Query("select description from stock_storeroom where storeroom_id=".$_SESSION['int_current_storeroom']);

  $form->addElement('header', '', 'Return Batch For Product '.$qry_storeroom->FieldByName('description')); 
  $form->addElement('static', '', 'Product: '.$qry_batch->FieldByName('product_description')); 
  $form->addElement('static', '', 'Batch code: '.$qry_batch->FieldByName('batch_code')); 
  $form->addElement('hidden', 'id', $_GET['id']);
  $form->addElement('hidden', 'product_id', $qry_batch->FieldByName('product_id'));
  $form->addElement('hidden', 'action', 'process');
  $form->addElement('text', 'transfer_description', 'Description:');
  $form->addElement('text', 'transfer_quantity', 'Quantity:');



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

	$qry = new Query("select * from ".Monthalize("stock_storeroom_batch")." where batch_id=".$_GET['id']." and storeroom_id=".$_SESSION['int_current_storeroom']." and (stock_available-stock_reserved>=$element_value)");
    	if ($qry->RowCount() > 0) {
		if ($element_value<=0) 
			return false;
		return true;
	}

    
   	return false;

}



  $form->registerRule('check_stock','function','fn_check_stock'); 
  $form->addRule('transfer_quantity','Insufficient stock currently available or invalid entry!','check_stock'); 

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


//=================== load variables
//
// Fills with some defaults values
//

	$form->setDefaults(array(
		'transfer_description'  => 'WASTE'));

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
    $msg = "";
    if ($msg=="") {
    	$confirm = "";
    	// for a new record
	$parentRefresh = false;
	$qry_exists= new Query("BEGIN");
	$parentRefresh = true;
	$msg = updateStoreroomBatch($_SESSION['int_current_storeroom'],
				$values['id'],
				-$values['transfer_quantity'],
				0,
				0);

	$msg .= updateStoreroomProduct($_SESSION['int_current_storeroom'],
				$values['product_id'],
				-$values['transfer_quantity'],
				0,
				0);	


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
			transfer_status,
			user_id_dispatched
			)
			VALUES (
			".$values['transfer_quantity'].",
			'".addslashes($values['transfer_description'])."',
			'".Date("Y-m-d h:i:s",time())."',
			1,
			".$_SESSION['int_user_id'].",
			".$_SESSION['int_current_storeroom'].",
			0,
			".$values['product_id'].",
			".$values['id'].",
			".TYPE_RETURNED.",
			".STATUS_COMPLETED.",
			".$_SESSION['int_user_id']."
			)";
//	echo $str_update;
	$qry_exists->Query($str_update);

	if ($qry_exists->b_error==true) $msg.="Error updating";

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
