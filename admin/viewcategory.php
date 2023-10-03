<?
/**
* 
* @version 	$Id: viewcategory.php,v 1.1.1.1 2006/02/14 05:03:57 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Category Edit
* @name  	viewcategory.php
* 
* This file uses the pear QuickForm components to display a dialog
* which lets the user modify category information
* 
* Get Parameters: 
* $_GET[id]		The category_id to load if you want to edit a category.  Otherwise
*			the insert categorypage will be shown
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
	$form->addElement('header', '', 'New Category'); 
  else 
	$form->addElement('header', '', 'View/Modify Category Master');

  $form->addElement('hidden', 'category_id', '');
  $form->addElement('text', 'category_code', 'Category Code:');
  $form->addElement('text', 'category_description', 'Category Name:');
  $form->addElement('text', 'hsn', 'HSN:');


//
// category list
//
  $cat_list = buildCategoryList();
  $cat_list2[0]='Root';
  for ($i=0;$i<count($cat_list);$i++) {
    $cat_list2[$cat_list[$i]["category_id"]] =  $cat_list[$i]["category_description"];
  }
  $cat_select =& $form->addElement('select', 'parent_category_id', 'Parent Category:', $cat_list2);


      $arr_is_perishable[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
      $arr_is_perishable[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
      $form->addGroup($arr_is_perishable, 'is_perishable', 'Is Perishable:');
    


//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules

//  $form->addRule('category_code', 'Category code is a required field', 'required', null, 'client');

  $form->addRule('category_description', 'You must enter a name for the category', 'required',null,'client');




/**
 * Check for duplicate category code before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */
function fn_check_duplicates($element_name,$element_value) {

    if (!empty($_GET["category_id"])) {
	    $existQuery=new Query("select category_description from stock_category where category_code='$element_value' and category_id<>".$_GET["category_id"]);
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }
    } else {
    	    $existQuery=new Query("select category_description from stock_category where category_code='$element_value'");
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }

    }
   return true;

 }


function fn_check_category($element_name, $element_value) {
	
    if (!empty($_GET["category_id"])) {
		if ($_GET['category_id'] == $element_value)
			return false;
	}
	return true;
}

	$form->registerRule('check_duplicates','function','fn_check_duplicates');
	$form->addRule('category_code','This category code is already used!','check_duplicates');
	
	$form->registerRule('check_category','function','fn_check_category');
	$form->addRule('parent_category_id','Root category and category cannot be the same', 'check_category');
	
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
				stock_category
			WHERE
				category_id=".$_GET['id']);

	$form->setDefaults(array(
		'category_id'  => $sql->FieldByName("category_id"),
		'category_code'  => $sql->FieldByName("category_code"),
		'category_description'  => stripslashes( $sql->FieldByName("category_description") ),
		'parent_category_id' => $sql->FieldByName("parent_category_id"),
        'is_perishable' => $sql->FieldByName('is_perishable'),
		'hsn' => $sql->FieldByName('hsn')
	));

    $cat_select->setValue($sql->FieldByName("parent_category_id")); 

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
	$values["category_description"]=$values["category_description"];
	$parentRefresh = false;

    	if ($values['category_id']<=0) {
	    	$stUpdate = "
			INSERT INTO stock_category (
				category_code,
				category_description,
				parent_category_id,
				is_perishable,
				is_modified,
				hsn
			) 
			VALUES (
				'".$values['category_code']."',
				'".addquotes($values['category_description'])."',
				".$values['parent_category_id'].",
				'".$values['is_perishable']."',
				'Y',
				'".$values['hsn']."'
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
			UPDATE stock_category SET
				category_code='".$values['category_code']."',
				category_description='".addquotes($values['category_description'])."',
				parent_category_id=".$values['parent_category_id'].",
				is_perishable='".$values['is_perishable']."',
				is_modified='Y',
				hsn='".$values['hsn']."'
			WHERE category_id=".$values['category_id'];
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
