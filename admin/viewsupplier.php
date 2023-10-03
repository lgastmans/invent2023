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
	$form->addElement('header', '', 'New Supplier'); 
  else 
	$form->addElement('header', '', 'View/Modify Supplier');


  $form->addElement('hidden', 'supplier_id', '');
  $form->addElement('text', 'supplier_code', 'Supplier Code:');
  $form->addElement('text', 'supplier_abbreviation', 'Supplier abbreviation:');
  $form->addElement('text', 'supplier_name', 'Supplier Name:');
  $form->addElement('text', 'contact_person', 'Contact Person:');
  $form->addElement('text', 'supplier_address', 'Address:');
  $form->addElement('text', 'supplier_city', 'City:');
  $form->addElement('text', 'supplier_state', 'State:');
  $form->addElement('text', 'supplier_zip', 'Zipcode:');
  $form->addElement('text', 'supplier_phone', 'Phone:');
  $form->addElement('text', 'supplier_cell', 'Cell:');
  $form->addElement('text', 'supplier_email', 'Email:');

  $radio[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
  $radio[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
  $form->addGroup($radio, 'is_supplier_delivering', 'Supplier Delivers:');

  $radio_active[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
  $radio_active[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
  $form->addGroup($radio_active, 'is_active', 'Supplier is active:');

  $form->addElement('text', 'commission_percent', 'Commission percent:');
  $form->addElement('text', 'commission_percent_2', 'Commission percent 2:');
  $form->addElement('text', 'commission_percent_3', 'Commission percent 3:');
  $form->addElement('text', 'supplier_discount', 'Discount on Price:');
  $form->addElement('text', 'supplier_type', 'Supplier Type:');
  $form->addElement('text', 'trust', 'Trust:');
  $form->addElement('text', 'supplier_TIN', 'TIN:');
  $form->addElement('text', 'supplier_CST', 'CST:');

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
				stock_supplier
			WHERE
				supplier_id=$id");

	$form->setDefaults(array(
		'supplier_id'  => $sql->FieldByName("supplier_id"),
		'supplier_code'  => stripslashes($sql->FieldByName("supplier_code")),
		'supplier_name'  => stripslashes($sql->FieldByName("supplier_name")),
		'supplier_abbreviation'  => stripslashes($sql->FieldByName("supplier_abbreviation")),
		'contact_person'  => stripslashes($sql->FieldByName("contact_person")),
		'supplier_address'  => stripslashes($sql->FieldByName("supplier_address")),
		'supplier_city'  => stripslashes($sql->FieldByName("supplier_city")),
		'supplier_state'  => stripslashes($sql->FieldByName("supplier_state")),
		'supplier_zip'  => stripslashes($sql->FieldByName("supplier_zip")),
		'supplier_phone'  => stripslashes($sql->FieldByName("supplier_phone")),
		'supplier_cell'  => stripslashes($sql->FieldByName("supplier_cell")),
		'supplier_email'  => stripslashes($sql->FieldByName("supplier_email")),

		'is_supplier_delivering'  => stripslashes($sql->FieldByName("is_supplier_delivering")),
		'is_active' => stripslashes($sql->FieldByName('is_active')),
		'commission_percent'  => $sql->FieldByName("commission_percent"),
		'commission_percent_2'  => $sql->FieldByName("commission_percent_2"),
		'commission_percent_3'  => $sql->FieldByName("commission_percent_3"),
                'supplier_discount' => $sql->FieldByName("supplier_discount"),
		'supplier_type'  => $sql->FieldByName("commission_percent"),
                'trust' => $sql->FieldByName('trust'),
                'supplier_TIN' => $sql->FieldByName('supplier_TIN'),
                'supplier_CST' => $sql->FieldByName('supplier_CST')
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
//	$values["measurement_unit"]=strtoupper($values["measurement_unit"]);
	$parentRefresh = false;

    	if ($values['supplier_id']<=0) {
	    	$stUpdate = "
		INSERT INTO stock_supplier (
		supplier_code,
		supplier_name,
		supplier_abbreviation,
		contact_person,
		supplier_address,
		supplier_city,
		supplier_state,
		supplier_zip,
		supplier_phone,
		supplier_cell,
		supplier_email,
		is_supplier_delivering,
		commission_percent,
		commission_percent_2,
		commission_percent_3,
                supplier_discount,
		supplier_type,
                trust,
                supplier_TIN,
                supplier_CST,
				is_active
			) 
			VALUES (
			'".addslashes($values['supplier_code'])."',
			'".addslashes($values['supplier_name'])."',
			'".addslashes($values['supplier_abbreviation'])."',
			'".addslashes($values['contact_person'])."',
			'".addslashes($values['supplier_address'])."',
			'".addslashes($values['supplier_city'])."',
			'".addslashes($values['supplier_state'])."',
			'".addslashes($values['supplier_zip'])."',
			'".addslashes($values['supplier_phone'])."',
			'".addslashes($values['supplier_cell'])."',
			'".addslashes($values['supplier_email'])."',
			'".$values['is_supplier_delivering']."',
			'".$values['commission_percent']."',
			'".$values['commission_percent_2']."',
			'".$values['commission_percent_3']."',
                        '".$values['supplier_discount']."',
			'".addslashes($values['supplier_type'])."',
                        '".addslashes($values['trust'])."',
                        '".addslashes($values['supplier_TIN'])."',
                        '".addslashes($values['supplier_CST'])."',
						'".addslashes($values['is_active'])."'
			)
			";

//			die($stUpdate);

			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			}
			
			$existQuery->Free();
			$parentRefresh=true;
	} else {
		
		$stUpdate="UPDATE stock_supplier SET
			supplier_code='".addslashes($values['supplier_code'])."',
			supplier_name='".addslashes($values['supplier_name'])."',
			supplier_abbreviation='".addslashes($values['supplier_abbreviation'])."',
			contact_person='".addslashes($values['contact_person'])."',
			supplier_address='".addslashes($values['supplier_address'])."',
			supplier_city='".addslashes($values['supplier_city'])."',
			supplier_state='".addslashes($values['supplier_state'])."',
			supplier_zip='".addslashes($values['supplier_zip'])."',
			supplier_phone='".addslashes($values['supplier_phone'])."',
			supplier_cell='".addslashes($values['supplier_cell'])."',
			supplier_email='".addslashes($values['supplier_email'])."',
			is_supplier_delivering='".addslashes($values['is_supplier_delivering'])."',
			commission_percent='".addslashes($values['commission_percent'])."',
			commission_percent_2='".addslashes($values['commission_percent_2'])."',
			commission_percent_3='".addslashes($values['commission_percent_3'])."',
                        supplier_discount='".$values['supplier_discount']."',
			supplier_type='".addslashes($values['supplier_type'])."',
                        trust='".addslashes($values['trust'])."',
                        supplier_TIN='".addslashes($values['supplier_TIN'])."',
                        supplier_CST='".addslashes($values['supplier_CST'])."',
						is_active='".addslashes($values['is_active'])."'
			where supplier_id=".$values['supplier_id'];
			
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
