<?
/**
* 
* @version 	$Id: viewmeasurement.php,v 1.1.1.1 2006/02/14 05:03:58 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Category Edit
* @name  	viewcategory.php
* 
* This file uses the pear QuickForm components to display a dialog
* which lets the user modify measurement unit information
* 
* Get Parameters: 
* $_GET[id]		The category_id to load if you want to edit a measurement record.  Otherwise
*			the insert measurement unit page will be shown
* Variables:
* $form 			The HTML QuickForm instance
* $bool_can_modify_record	True if user showing this page has modify rights
*/

//  error_reporting(E_ERROR|E_WARNING);
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once("../common/functions.inc.php");
  require('QuickForm.php');
  
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
	$form->addElement('header', '', 'New Measurement Unit'); 
  else 
	$form->addElement('header', '', 'View/Modify Measurement Unit');

  $form->addElement('hidden', 'measurement_unit_id', '');
  $form->addElement('text', 'measurement_unit', 'Measurement Unit:');
  $radio4[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
  $radio4[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
  $form->addGroup($radio4, 'is_decimal', 'Is Decimal:');



//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules

  $form->addRule('measurement_unit', 'Measurement Unit is a required field', 'required', null, 'client');

  
/**
 * Check for duplicate category code before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */
function fn_check_duplicates($element_name,$element_value) {

    if (!empty($_GET["measurement_unit_id"])) {
	    $existQuery=new Query("select measurment_unit from stock_measurement_unit where measurement_unit='$element_value' and measurement_unit_id<>".$_GET["measurement_unit_id"]);
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }
    } else {
    	    $existQuery=new Query("select measurement_unit_id from stock_measurement_unit where measurement_unit='$element_value'");
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }

    }
   return true;

 }



  $form->registerRule('check_duplicates','function','fn_check_duplicates'); $form->addRule('measurement_unit','This measurement unit is already used!','check_duplicates'); 

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
				stock_measurement_unit
			WHERE
				measurement_unit_id=$id");

	$form->setDefaults(array(
		'measurement_unit_id'  => $sql->FieldByName("measurement_unit_id"),
		'measurement_unit'  => $sql->FieldByName("measurement_unit"),
		'is_decimal' => $sql->FieldByName("is_decimal")

	));


  } else	
	$form->setDefaults(array(
		'is_decimal' => 'Y'

	));



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

    	if ($values['measurement_unit_id']<=0) {
	    	$stUpdate = "
		INSERT INTO stock_measurement_unit (
			measurement_unit,
			is_decimal,
			is_modified
			) 
			VALUES (
			'".$values['measurement_unit']."',
			'".$values['is_decimal']."',
			'Y'
			)
			";
			//die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			}
			
			$existQuery->Free();
			$parentRefresh=true;

			
	} else {
		
		$stUpdate="
			UPDATE stock_measurement_unit
			SET
				measurement_unit='".$values['measurement_unit']."',
				is_decimal='".$values['is_decimal']."',
				is_modified='Y'
			WHERE measurement_unit_id=".$values['measurement_unit_id'];
//			die($stUpdate);
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
