<?
/**
* 
* @version 	$Id: viewtaxdefinition.php,v 1.2 2006/02/25 06:29:23 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		06 Dec 2005
* @module 	Tax Edit
* @name  	viewstoreroom.php
* 
* This file uses the pear QuickForm components to display a dialog
* which lets the user modify tax definition information
* 
* Get Parameters: 
* $_GET[id]		The definition_id to load if you want to edit a definition record.  Otherwise
*			the insert definition unit page will be shown
* Variables:
* $form 			The HTML QuickForm instance
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
  $bool_can_modify_record = false;

  $bool_can_modify_record = (getModuleAccessLevel('Admin')>1);

  if ($_SESSION["int_user_type"]>1) {	
	$bool_can_modify_record = true;
  } 
	
//
// if a get parameter is passed, then edit, otherwise insert
//
  if (empty($_GET["id"])) 
	$form->addElement('header', '', 'New Storeroom'); 
  else 
	$form->addElement('header', '', 'View/Modify Storeroom');

  $form->addElement('hidden', 'storeroom_id', '');
  $form->addElement('text', 'code', 'Storeroom Code:');
  $form->addElement('text', 'description', 'Storeroom Name:');
  $form->addElement('text', 'location', 'Location:');
  $form->addElement('text', 'bill_description', 'Bill Description:');
  $form->addElement('text', 'bill_order_description', 'Order Bill Description:');
  $form->addElement('text', 'bill_credit_account', 'Storeroom FS Account:');

  $radio[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
  $radio[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
  $form->addGroup($radio, 'is_cash_taxed', 'Tax Cash Bills:');

  $radio2[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
  $radio2[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
  $form->addGroup($radio2, 'is_account_taxed', 'Tax Account Bills:');

  $radio3[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
  $radio3[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
  $form->addGroup($radio3, 'can_bill_cash', 'Allow Cash Billing:');

  $radio6[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
  $radio6[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
  $form->addGroup($radio6, 'can_bill_creditcard', 'Allow Credit Card Billing:');

  $radio4[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
  $radio4[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
  $form->addGroup($radio4, 'can_bill_pt_account', 'Allow PT Account Billing:');

  $radio5[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
  $radio5[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
  $form->addGroup($radio5, 'can_bill_fs_account', 'Allow FS Account Billing:');
  
//
// tax list
//
  $tax_list = buildTaxList();
  for ($i=0;$i<count($tax_list);$i++) {
    $tax_list2[$tax_list[$i]["tax_id"]] =  $tax_list[$i]["tax_description"];
  }

  $tax_select =& $form->addElement('select', 'default_tax_id', 'New Product Default Tax:', $tax_list2);

//
// first supplier list
//
  $qry = new Query("select * from stock_supplier order by supplier_name");
  $arr_sup_list[0]='(none)';
  for ($i=0;$i<$qry->RowCount();$i++) {
    $arr_sup_list[$qry->FieldByName("supplier_id")] =  $qry->FieldByName("supplier_name");

  	$qry->Next();
  }

  $sup_select =& $form->addElement('select', 'default_supplier_id', 'New Product Default Supplier:', $arr_sup_list);


//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules

//  $form->addRule('definition_percent', 'Percent is a required field', 'required', null, 'client');
//  $form->addRule('definition_description', 'Percent is a required field', 'required', null, 'client');
  
/**
 * Check for duplicate category code before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */
function fn_check_duplicates($element_name,$element_value) {

    if (!empty($_GET["definition_id"])) {
	    $existQuery=new Query("select definition_description from ".Monthalize('stock_tax_definition')." where definition_description='$element_value' and definition_id<>".$_GET["definition_id"]);
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }
    } else {
    	    $existQuery=new Query("select definition_id from ".Monthalize('stock_tax_definition')." where definition_description='$element_value'");
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }

    }
   return true;

 }



//  $form->registerRule('check_duplicates','function','fn_check_duplicates'); $form->addRule('definition_name','This Definition is already used.  Please make it unique (ex: TNGST 4%)!','check_duplicates'); 

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


//=================== load variables
//
// Fills with some defaults values
//
  if (!empty($_GET['id'])) {
    $id = $_GET["id"];
	 $sql = new Query("
			SELECT
				*
			FROM
				stock_storeroom
			WHERE
				storeroom_id=$id");

	$form->setDefaults(array(
		'storeroom_id'  => $sql->FieldByName("storeroom_id"),
		'code' => stripslashes($sql->FieldByName('storeroom_code')),
		'description'  => stripslashes($sql->FieldByName("description")),
		'location'  => stripslashes($sql->FieldByName("location")),
		'bill_description'  => stripslashes($sql->FieldByName("bill_description")),
                'bill_order_description' => stripslashes($sql->FieldByName("bill_order_description")),
		'bill_credit_account'  => $sql->FieldByName("bill_credit_account"),
		'is_cash_taxed'  => $sql->FieldByName("is_cash_taxed"),
		'is_account_taxed'  => $sql->FieldByName("is_account_taxed"),
		'can_bill_cash'  => $sql->FieldByName("can_bill_cash"),
		'can_bill_creditcard' => $sql->FieldByName("can_bill_creditcard"),
		'can_bill_fs_account'  => $sql->FieldByName("can_bill_fs_account"),
		'can_bill_pt_account'  => $sql->FieldByName("can_bill_pt_account")
	));

    $tax_select->setValue($sql->FieldByName("default_tax_id")); 
    $sup_select->setValue($sql->FieldByName("default_supplier_id"));



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
//	$values["measurement_unit"]=strtoupper($values["measurement_unit"]);
	$parentRefresh = false;

    	if ($values['storeroom_id']<=0) {
	    	$stUpdate = "
		INSERT INTO stock_storeroom (
			storeroom_code,
			description,
			location,
			is_account_taxed,
			is_cash_taxed,
			can_bill_cash,
			can_bill_creditcard,
			can_bill_pt_account,
			can_bill_fs_account,
			bill_description,
                        bill_order_description,
			bill_credit_account,
			default_tax_id,
			default_supplier_id
			) 
			VALUES (
			'".addslashes($values['code'])."',
			'".addslashes($values['description'])."',
			'".addslashes($values['location'])."',
			'".$values['is_account_taxed']."',
			'".$values['is_cash_taxed']."',
			'".$values['can_bill_cash']."',
			'".$values['can_bill_creditcard']."',
			'".$values['can_bill_pt_account']."',
			'".$values['can_bill_fs_account']."',
			'".addslashes($values['bill_description'])."',
			'".addslashes($values['bill_order_description'])."',
			'".($values['bill_credit_account'])."',
			".$values['default_tax_id'].",
			".$values['default_supplier_id']."
			)
			";
			//die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			}
			
			$existQuery->Free();
			$parentRefresh=true;

			
	} else {
		
		$stUpdate="UPDATE stock_storeroom SET
			storeroom_code='".addslashes($values['code'])."',
			description='".addslashes($values['description'])."',
			location='".addslashes($values['location'])."',
			bill_description='".addslashes($values['bill_description'])."',
			bill_order_description='".addslashes($values['bill_order_description'])."',
			bill_credit_account='".addslashes($values['bill_credit_account'])."',
			is_account_taxed='".addslashes($values['is_account_taxed'])."',
			is_cash_taxed='".addslashes($values['is_cash_taxed'])."',
			can_bill_pt_account='".addslashes($values['can_bill_pt_account'])."',
			can_bill_fs_account='".addslashes($values['can_bill_fs_account'])."',
			can_bill_cash='".addslashes($values['can_bill_cash'])."',
			can_bill_creditcard='".addslashes($values['can_bill_creditcard'])."',
			default_tax_id=".$values['default_tax_id'].",
			default_supplier_id=".$values['default_supplier_id']."
			where storeroom_id=".$values['storeroom_id'];

//			die($stUpdate);
			echo $stUpdate;

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
