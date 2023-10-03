<?
/**
* 
* @version 	$Id: viewtax.php,v 1.2 2006/02/20 03:58:37 cvs Exp $
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
	$form->addElement('header', '', 'New Tax'); 
  else 
	$form->addElement('header', '', 'View/Modify Tax');

  $form->addElement('hidden', 'tax_id', '');
  $form->addElement('text', 'tax_description', 'Tax Name:');
  $is_active = $form->addElement('checkbox', 'is_active', 'Active:');

  $tax_list = buildTaxDefinitionList();

  $tax_list1[0]="(None)";
  $tax_list2[0]="(None)";
  $tax_list3[0]="(None)";
  $tax_list4[0]="(None)";
  $tax_list5[0]="(None)";

  for ($i=0;$i<count($tax_list);$i++) {
    $tax_list1[$tax_list[$i]["definition_id"]] =  $tax_list[$i]["definition_description"];
    $tax_list2[$tax_list[$i]["definition_id"]] =  $tax_list[$i]["definition_description"];
    $tax_list3[$tax_list[$i]["definition_id"]] =  $tax_list[$i]["definition_description"];
    $tax_list4[$tax_list[$i]["definition_id"]] =  $tax_list[$i]["definition_description"];
    $tax_list5[$tax_list[$i]["definition_id"]] =  $tax_list[$i]["definition_description"];
  }

  $tax_select1 =& $form->addElement('select', 'definition_id1', 'Tax 1:', $tax_list1);
  $tax_select2 =& $form->addElement('select', 'definition_id2', 'Tax 2:', $tax_list2);
  $tax_select3 =& $form->addElement('select', 'definition_id3', 'Tax 3:', $tax_list3);
  $tax_select4 =& $form->addElement('select', 'definition_id4', 'Tax 4:', $tax_list4);
  $tax_select5 =& $form->addElement('select', 'definition_id5', 'Tax 5:', $tax_list5);


//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules

  $form->addRule('tax_description', 'Tax name is a required field', 'required', null, 'client');
  
/**
 * Check for duplicate category code before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */

function fn_check_duplicates($element_name,$element_value) {

    if (!empty($_GET["tax_id"])) {
	    $existQuery=new Query("select tax_description from ".Monthalize('stock_tax')." where tax_description='$element_value' and tax_id<>".$_GET["tax_id"]);
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }
    } else {
    	    $existQuery=new Query("select tax_id from ".Monthalize('stock_tax')." where tax_description='$element_value'");
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }

    }
   return true;

 }



  $form->registerRule('check_duplicates','function','fn_check_duplicates'); $form->addRule('tax_description','This Tax Name is already used.  Please make it unique!','check_duplicates'); 

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


//=================== load variables
//
// Fills with some defaults values
//
  if (!empty($_GET['id'])) {

  	$id = $_GET['id'];

	$sql = new Query("
			SELECT
				*
			FROM
				".Monthalize('stock_tax')."
			WHERE
				tax_id=$id");

	$sql2 = new Query("SELECT * FROM ".Monthalize('stock_tax_links')." where tax_id=" . $_GET['id']." ORDER by tax_order");

	$arr_defs = array(
		'tax_id'  => $sql->FieldByName("tax_id"),
		'tax_description'  => $sql->FieldByName("tax_description")
	);


	for ($i=0; $i  < $sql2->RowCount(); $i++) {
		$arr_defs[ "definition_id".$i ]=$sql2->FieldByName('tax_definition_id');

		$sql2->Next();
	}

	$form->setDefaults( $arr_defs );
	@$tax_select1->setValue($arr_defs["definition_id0"]);
	@$tax_select2->setValue($arr_defs["definition_id1"]);
	@$tax_select3->setValue($arr_defs["definition_id2"]);
	@$tax_select4->setValue($arr_defs["definition_id3"]);
	@$tax_select5->setValue($arr_defs["definition_id4"]);

	if ( $sql->FieldByName('is_active') == 'Y' )
		$is_active->setChecked(1);
	else
		$is_active->setChecked(0);

  }
  else
  	$is_active->setChecked(1);


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

    	if ($values['tax_id']<=0) {

    		$flag = ($values['is_active'] ? 'Y' : 'N');

	    	$stUpdate = "
				INSERT INTO ".Monthalize('stock_tax')." (
					tax_description,
					is_modified,
					is_active
				) 
				VALUES (
					'".$values['tax_description']."',
					'Y',
					'".$flag."'
				)";
			//die($stUpdate);

			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			} else {
				$int_tax_id = $existQuery->getInsertedID();
				$str_insert_links = "INSERT INTO ".Monthalize('stock_tax_links')." (
					tax_id, tax_definition_id,tax_order) VALUES ";
				$int_num_defs = 0;
				for ($i=0;$i<5;$i++) {	
					if (!empty($values['definition_id'.$i])) {
						$str_insert_links.="(".$int_tax_id.",".$values['definition_id'.$i].",".(++$int_num_defs)."),";
	//					$int_num_defs++;
					}
				}
			
				if ($int_num_defs > 0) {
					$str_insert_links = substr($str_insert_links,0,strlen($str_insert_links)-1);
					$existQuery->Query($str_insert_links);

					if ($existQuery->GetErrorMessage()<>"") {
						  $msg = "There was an error while trying to add the record!".$existQuery->GetErrorMessage();
					}
				}
			}
			
			$existQuery->Free();
			$parentRefresh=true;

			
	} else {

		$flag = ($values['is_active'] ? 'Y' : 'N');

		$stUpdate="
			UPDATE ".Monthalize('stock_tax')."
			SET
				tax_description='".$values['tax_description']."',
				is_modified='Y',
				is_active='".$flag."'
			WHERE tax_id=".$values['tax_id'];

			//die($stUpdate);

			if (!$existQuery= new Query($stUpdate)) {
				$msg = "There was an error while trying to save your information! ".$existQuery->GetErrorMessage();
			} else {
				$existQuery->Query("DELETE FROM ".Monthalize('stock_tax_links')." WHERE tax_id=".$values['tax_id']);

				$str_insert_links = "INSERT INTO ".Monthalize('stock_tax_links')." (
					tax_id, tax_definition_id,tax_order) VALUES ";
				$int_num_defs = 0;
				for ($i=0;$i<5;$i++) {	
					if (!empty($values['definition_id'.$i])) {
						$str_insert_links.="(".$values['tax_id'].",".$values['definition_id'.$i].",".(++$int_num_defs)."),";
//						$int_num_defs++;
					}
				}
			
				if ($int_num_defs > 0) {
					$str_insert_links = substr($str_insert_links,0,strlen($str_insert_links)-1);
				}
				if ($int_num_defs > 0) {

					$existQuery->Query($str_insert_links);
				}
				
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