<?
/**
* 
* @version 	$Id: viewtaxdefinition.php,v 1.2 2006/02/25 06:29:23 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		06 Dec 2005
* @module 	Tax Edit
* @name  	viewtaxdefinition.php
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
	$form->addElement('header', '', 'New Tax Definition'); 
  else 
	$form->addElement('header', '', 'View/Modify Tax Definition');

  $form->addElement('hidden', 'definition_id', '');
  $form->addElement('text', 'definition_description', 'Definition Name:');
  $form->addElement('text', 'definition_percent', 'Percent:');
  $form->addElement('text', 'definition_explanation', 'Description:');
  $tax_select =& $form->addElement('select', 'definition_type', 'Type:', getTaxTypeList());

//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules

  $form->addRule('definition_percent', 'Percent is a required field', 'required', null, 'client');
  $form->addRule('definition_description', 'Percent is a required field', 'required', null, 'client');
  
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



  $form->registerRule('check_duplicates','function','fn_check_duplicates'); $form->addRule('definition_name','This Definition is already used.  Please make it unique (ex: TNGST 4%)!','check_duplicates'); 

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
				".Monthalize('stock_tax_definition')."
			WHERE
				definition_id=$id");

	$form->setDefaults(array(
		'definition_id'  => $sql->FieldByName("definition_id"),
		'definition_description'  => $sql->FieldByName("definition_description"),
		'definition_percent'  => $sql->FieldByName("definition_percent"),
		'definition_explanation'  => $sql->FieldByName("definition_explanation"),
		'definition_type'  => $sql->FieldByName("definition_type")
	));
	$tax_select->setValue($sql->FieldByName("definition_type"));


  } else	
	$form->setDefaults(array(
		'definition_percent' => '4'

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

    	if ($values['definition_id']<=0) {
	    	$stUpdate = "
		INSERT INTO ".Monthalize('stock_tax_definition')." (
			definition_description,
			definition_percent,
			definition_explanation,
			definition_type
			) 
			VALUES (
			'".$values['definition_description']."',
			'".$values['definition_percent']."',
			'".$values['definition_explanation']."',
			'".$values['definition_type']."'
			)
			";
			//die($stUpdate);
			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			}
			
			$existQuery->Free();
			$parentRefresh=true;

			
	} else {
		
		$stUpdate="UPDATE ".Monthalize('stock_tax_definition')." SET
			definition_description='".$values['definition_description']."',
			definition_percent='".$values['definition_percent']."',
			definition_explanation='".$values['definition_explanation']."',
			definition_type='".$values['definition_type']."'
			where definition_id=".$values['definition_id'];

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
