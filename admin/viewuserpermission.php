<?
/**
* 
* @version 	$Id:
* @copyright 	Cynergy Software 2006
* @author	Luk Gastmans
* @date		8 april 2006
* @module 	User Permissions
* @name  	viewuserpermission.php
* 
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

  require_once('HTML/QuickForm.php');
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
	$form->addElement('header', '', 'New Permission'); 
  else 
	$form->addElement('header', '', 'View/Modify Permission');

  $form->addElement('hidden', 'user_id', '');
  $form->addElement('hidden', 'permission_id', '');

  $qry_module=new Query("select * from module where active='Y'");
  $arr_module_list[0] =  '- Select -';	
  for ($i=0;$i<$qry_module->RowCount();$i++) {
    		$arr_module_list[$qry_module->FieldByName('module_id')] =  $qry_module->FieldByName('description');
		$qry_module->Next();
  }

  $module_select = &$form->addElement('select', 'module_id', 'Storeroom:', $arr_module_list);

  $qry_storeroom=new Query("select * from stock_storeroom");
  for ($i=0;$i<$qry_storeroom->RowCount();$i++) {
    		$arr_storeroom_list[$qry_storeroom->FieldByName('storeroom_id')] =  $qry_storeroom->FieldByName('description');
		$qry_storeroom->Next();
  }

  $storeroom_select =& $form->addElement('select', 'storeroom_id', 'Storeroom:', $arr_storeroom_list);


  $access_list = array(
		ACCESS_NONE=>"None",
		ACCESS_READ=>"Read",
		ACCESS_WRITE=>"Read/Write",
		ACCESS_ADMIN=>"Admin"
	);

  $access_select = &$form->addElement('select', 'access_level', 'Access Level:', $access_list);


//====================
  $form->applyFilter('__ALL__', 'trim');


  
/**
 * Check for duplicate before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */

function fn_check_duplicates($element_name,$element_value) {

    if (!empty($_GET["user_id"])) {
	    $existQuery=new Query("select module_id from user where deleted='N' and username='$element_value' and user_id<>".$_GET["user_id"]);
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }
    } else {
    	    $existQuery=new Query("select user_id from user where deleted='N' and username='$element_value'");
	    if ($existQuery->RowCount() > 0) {
	      return false;
	    }

    }
   return true;

 }



//  $form->registerRule('check_duplicates','function','fn_check_duplicates'); $form->addRule('username','This username is already used.  Please make it unique!','check_duplicates'); 
  $form->addRule('module_id', 'Select a module', 'required', null, 'client');

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
				user_permissions
			WHERE
				permission_id=$id");

	$arr_defs = array(
		'permission_id'  => $sql->FieldByName("permission_id"),
		'user_id' => $sql->FieldByName("user_id")
	);	

	$form->setDefaults( $arr_defs );
	@$access_select->setValue($sql->FieldByName("access_level"));
	@$module_select->setValue($sql->FieldByName("module_id"));
	@$storeroom_select->setValue($sql->FieldByName("storeroom_id"));

  } else {
	$arr_defs = array(
		'user_id' => $_GET['user_id']
	);	

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

    	if ($values['permission_id']<=0) {
	    	$stUpdate = "
		INSERT INTO user_permissions (
			user_id,
			module_id,
			access_level,
			storeroom_id
			) 
			VALUES (
			".intval($values['user_id']).",
			".intval($values['module_id']).",
			".intval($values['access_level']).",
			".intval($values['storeroom_id'])."
			)
			";
//			die($stUpdate);
 			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			} 
			
			$existQuery->Free();
			$parentRefresh=true;

			
	} else {
		
		$stUpdate="UPDATE user_permissions SET
			user_id=".intval($values['user_id']).",
			storeroom_id=".intval($values['storeroom_id']).",
			access_level=".intval($values['access_level']).",
			module_id=".intval($values['module_id'])."
			where permission_id=".$values['permission_id'];

//			die($stUpdate);

			if (!$existQuery= new Query($stUpdate)) {
				$msg = "There was an error while trying to save your information! ".$existQuery->GetErrorMessage();
			} else {
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
