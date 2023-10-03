<?
/**
* 
* @version 	$Id:
* @copyright 	Cynergy Software 2006
* @author	Luk Gastmans
* @date		8 april 2006
* @module 	User Edit
* @name  	viewuser.php
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

  require_once 'HTML/QuickForm.php';
  $form =& new HTML_QuickForm('frmTest', 'get');
//
//  check permissions
//
  $bool_can_modify_record = false;

  $bool_can_modify_record = (getModuleAccessLevel('Admin')>1);
  $int_access_level = getModuleAccessLevel('Admin');

  if ($_SESSION["int_user_type"]>1) {	
	$bool_can_modify_record = true;
  } 
	
//
// if a get parameter is passed, then edit, otherwise insert
//
	if (empty($_GET["id"])) 
		$form->addElement('header', '', 'New User'); 
	else 
		$form->addElement('header', '', 'View/Modify User');
	
	$form->addElement('hidden', 'user_id', '');
	$form->addElement('text', 'fusername', 'User Name:');
	$form->addElement('password', 'fpassword', 'Password:');
	$form->addElement('password', 'fconfirm', 'Confirm:');
	
	$qry_storeroom=new Query("select * from stock_storeroom");
	for ($i=0;$i<$qry_storeroom->RowCount();$i++) {
			$arr_storeroom_list[$qry_storeroom->FieldByName('storeroom_id')] =  $qry_storeroom->FieldByName('description');
			$qry_storeroom->Next();
	}
	
	if (($int_access_level > 2) && ($_SESSION["int_user_type"] > 1)) {
            $usertype[] = &HTML_QuickForm::createElement('radio', null, null, 'Normal', '1');
            $usertype[] = &HTML_QuickForm::createElement('radio', null, null, 'Admin', '2');
            $form->addGroup($usertype, 'user_type', 'User Type:');
            
            $arr_bill_date[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
            $arr_bill_date[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
            $form->addGroup($arr_bill_date, 'can_change_bill_date', 'Can change bill date:');
    
            $arr_price[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
            $arr_price[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
            $form->addGroup($arr_price, 'can_change_price', 'Can change price:');

            $arr_batch_edit[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y');
            $arr_batch_edit[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N');
            $form->addGroup($arr_batch_edit, 'can_edit_batch', 'Can edit batch details:');

            $storeroom_select =& $form->addElement('select', 'default_storeroom_id', 'Default Storeroom:', $arr_storeroom_list);
        }
        else {
            $usertype[] = &HTML_QuickForm::createElement('radio', null, null, 'Normal', '1', 'disabled');
            $usertype[] = &HTML_QuickForm::createElement('radio', null, null, 'Admin', '2', 'disabled');
            $form->addGroup($usertype, 'user_type', 'User Type:');
            
            $arr_bill_date[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y', 'disabled');
            $arr_bill_date[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N', 'disabled');
            $form->addGroup($arr_bill_date, 'can_change_bill_date', 'Can change bill date:');
    
            $arr_price[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y', 'disabled');
            $arr_price[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N', 'disabled');
            $form->addGroup($arr_price, 'can_change_price', 'Can change price:');

            $arr_batch_edit[] = &HTML_QuickForm::createElement('radio', null, null, 'Yes', 'Y', 'disabled');
            $arr_batch_edit[] = &HTML_QuickForm::createElement('radio', null, null, 'No', 'N', 'disabled');
            $form->addGroup($arr_batch_edit, 'can_edit_batch', 'Can edit batch details:');

            $storeroom_select =& $form->addElement('select', 'default_storeroom_id', 'Default Storeroom:', $arr_storeroom_list, 'disabled');
        }

	
	
	$pred_list = array(
			PO_PREDICT_NONE=>"None",
			PO_PREDICT_PREVIOUS=>"Prev. Month",
			PO_PREDICT_PREVIOUS_CURRENT=>"Prev and Current Month",
			PO_PREDICT_CURRENT=>"Current Month");
	
	$pred_select1 = &$form->addElement('select', 'po_prediction_method', 'Prediction Method:', $pred_list);

	$arr_color_scheme['standard'] = 'Standard';
	$arr_color_scheme['blue'] = 'Blue';
	$arr_color_scheme['purple'] = 'Purple';
	$arr_color_scheme['green'] = 'Green';
	$select_scheme = &$form->addElement('select', 'selected_color_scheme', 'Color Scheme:', $arr_color_scheme);
	
	$arr_font_size['small'] = 'Small';
	$arr_font_size['standard'] = 'Standard';
	$arr_font_size['large'] = 'Large';
	$select_font_size = &$form->addElement('select', 'selected_font_size', 'Font Size:', $arr_font_size);

	$arr_printing_type['1'] = 'Local printer';
	$arr_printing_type['2'] = 'network printer';
	$select_printing_type = &$form->addElement('select', 'selected_printing_type', 'Print to:', $arr_printing_type);



//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules

  $form->addRule('fusername', 'User name is a required field', 'required', null, 'client');
  $form->addRule('fpassword', 'Password is a required field', 'required', null, 'client');
  $form->addRule('fconfirm', 'Confirmation is a required field', 'required', null, 'client');
  
  
/**
 * Check for duplicate before posting
 *
 * @param   string    ignored
 * @param   string    the value of the product_code field
 *
 */

function fn_check_duplicates($element_name,$element_value) {

    if (!empty($_GET["user_id"])) {
	    $existQuery=new Query("select username from user where deleted='N' and username='$element_value' and user_id<>".$_GET["user_id"]);
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



  $form->registerRule('check_duplicates','function','fn_check_duplicates');
  $form->addRule('username','This username is already used.  Please make it unique!','check_duplicates'); 

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
				user
			WHERE
				user_id=$id");

	$arr_defs = array(
		'user_id'  => $sql->FieldByName("user_id"),
		'user_type'  => $sql->FieldByName("user_type"),
		'fusername'  => $sql->FieldByName("username"),
		'fpassword'  => base64_decode($sql->FieldByName("password")),
		'fconfirm'   => base64_decode($sql->FieldByName("password")),
		'can_change_bill_date' => $sql->FieldByName('can_change_bill_date'),
		'can_change_price' => $sql->FieldByName('can_change_price'),
                'can_edit_batch' => $sql->FieldByName('can_edit_batch')
	);	

	$form->setDefaults( $arr_defs );
	@$pred_select1->setValue($sql->FieldByName("po_prediction_method"));
	@$select_scheme->setValue($sql->FieldByName("color_scheme"));
	@$select_font_size->setValue($sql->FieldByName("font_size"));
	@$select_printing_type->setValue($sql->FieldByName("printing_type"));
	@$storeroom_select->setValue($sql->FieldByName("default_storeroom_id"));

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
	    $int_access_level = getModuleAccessLevel('Admin');

    	if ($values['user_id']<=0) {
	    	$stUpdate = "
		INSERT INTO user (
			username,
			password,
			user_type,
			default_storeroom_id,
			po_prediction_method,
			color_scheme,
			font_size,
			printing_type,
			can_change_bill_date,
			can_change_price,
                        can_edit_batch
			) 
			VALUES (
			'".$values['fusername']."',
			'".base64_encode($values['fpassword'])."',
			".intval($values['user_type']).",
			".intval($values['default_storeroom_id']).",
			".intval($values['po_prediction_method']).",
			'".$values['selected_color_scheme']."',
			'".$values['selected_font_size']."',
			".$values['selected_printing_type'].",
			'".$values['can_change_bill_date']."',
			'".$values['can_change_price']."',
                        '".$values['can_edit_batch']."'
			)
			";
//			die($stUpdate);

			if (!$existQuery= new Query($stUpdate)) {
			  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
			} 
			
			$existQuery->Free();
			$parentRefresh=true;

			
	} else {
	    if ($int_access_level > 2) {
		$stUpdate = "
		  UPDATE user
		  SET
			username='".$values['fusername']."',
			password='".base64_encode($values['fpassword'])."',
			user_type=".intval($values['user_type']).",
			default_storeroom_id=".intval($values['default_storeroom_id']).",
			po_prediction_method=".intval($values['po_prediction_method']).",
			color_scheme='".$values['selected_color_scheme']."',
			font_size='".$values['selected_font_size']."',
			printing_type=".$values['selected_printing_type'].",
			can_change_bill_date='".$values['can_change_bill_date']."',
			can_change_price='".$values['can_change_price']."',
                        can_edit_batch='".$values['can_edit_batch']."'
		  WHERE user_id=".$values['user_id'];
	    }
	    else {
		$stUpdate = "
		  UPDATE user
		  SET
			username='".$values['fusername']."',
			password='".base64_encode($values['fpassword'])."',
			po_prediction_method=".intval($values['po_prediction_method']).",
			color_scheme='".$values['selected_color_scheme']."',
			font_size='".$values['selected_font_size']."',
			printing_type=".$values['selected_printing_type']."
		  WHERE user_id=".$values['user_id'];
	    }

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
