<?
/**
* 
* @version 	$Id: transferbatch.php,v 1.1.1.1 2006/02/14 05:03:59 cvs Exp $
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
  require_once("../include/const.inc.php");

  require_once("../include/session.inc.php");
  
  require_once("../include/db.inc.php");
  require_once("../common/functions.inc.php");

//  ini_set("include_path", '/usr/share/pear/' . PATH_SEPARATOR . ini_get("include_path"));

  require_once 'HTML/QuickForm.php';
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
				*
			  FROM ".Monthalize('tbl_storeroom_batch')." 
			  WHERE
				batch_id=".$_GET['batch_id']." 
			  AND 
				storeroom_id=".$_SESSION['int_current_storeroom']);

  $form->addElement('header', '', 'Transfer From '); 
  $form->addElement('hidden', 'batch_id', $_GET['batch_id']);
  

  if ($qry_batch->b_error) {
	die('Error getting batch details.');
  }

  $qry_storeroom=new Query("select * from stock_storeroom where storeroom_id<>".$_SESSION['int_current_storeroom']);
  for ($i=0;$i<$qry_storeroom->RowCount();$i++) {
    		$arr_storeroom_list[$qry_storeroom->FieldByName('storeroom_id')] =  $qry_storeroom->FieldByName('storeroom_description')
		$qry_storeroom->Next();
  }
  $storeroom_select =& $form->addElement('select', 'storeroom_id', 'To Storeroom:', $arr_storeroom_list);


  $form->addElement('text', 'transfer_description', 'Description:');
  $form->addElement('text', 'transfer_quantity', 'Quantity:');



//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules



  $form->addRule('minimum_qty', 'Minimum quantity is not valid', 'numeric', null, 'client');



/**
 * Check for duplicate product code before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */
function fn_check_product($element_name, $element_value) {

	if (count($element_value)<2) {
	      return false;
    	}
    	$qry = new Query("select * from ".Monthalize("stock_storeroom_product")." where product_id=".$element_value[1]);
    	if ($qry->RowCount()>0) {
		return false;
	}

    
   	return true;

 }



  $form->registerRule('check_product','function','fn_check_product'); $form->addRule('product_sel','Product exists already or is not selected properly!','check_product'); 

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


//=================== load variables
//
// Fills with some defaults values
//
  if (!empty($_GET['id'])) {
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

  }



  if (empty($_GET["id"]))  {
  	if ($form->validate()) {
    // Form is validated, then processes the data
		$form->freeze();
	    	$form->process('saveForm', false);
	    	echo "\n<HR>\n";
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
    $msg = "";
    if ($msg=="") {
    	$confirm = "";
    	// for a new record
	$parentRefresh = false;

    	if ($values['product_id']<=0) {
	    	$stUpdate = "
		INSERT INTO ".Monthalize('stock_storeroom_product')." (
			product_id,
			storeroom_id,
			stock_minimum
			) 
			VALUES (
			".$values['product_sel'][1].",
			".$_SESSION['int_current_storeroom'].",
			".$values['minimum_qty']."
			)
			";
			//die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			}
			
			$existQuery->Free();
			$parentRefresh=true;

			
	} else {
		
		$stUpdate="UPDATE ".Monthalize('stock_storeroom_product')." SET
			stock_minimum=".$values['minimum_qty']."
			where product_id=".$values['product_id'];
			//die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
				$msg = "There was an error while trying to save your information! ".$existQuery->GetErrorMessage();
			} 
			$existQuery->Free();
			$parentRefresh=true;
		
	}
    
    	if (($msg=="") && ($confirm=="")) {
    		?><html><body><script language="JavaScript"><? 
		if ($parentRefresh) {
      			echo ("window.opener.document.location=window.opener.document.location.href;");
    		}
		?>
    		window.close(); </script></body></html>
    		<?
    		exit;
    	}
    }
   
}  
?>
