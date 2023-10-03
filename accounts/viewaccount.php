<?
/**
*
* @version 	$Id: viewaccount.php,v 1.1.1.1 2006/02/14 05:03:58 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		13 Dec 2005
* @module 	Account Edit
* @name  	viewaccount.php
*
* This file uses the pear QuickForm components to display a dialog
* which lets the user modify account profile information
*
* Get Parameters:
* $_GET[id]		The cost center id
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

  $bool_can_modify_record = (getModuleAccessLevel('Accounts')>1);

  if ($_SESSION["int_user_type"]>1) {
	$bool_can_modify_record = true;
  }

//
// load cost center information
//
  if (!empty($_GET['id'])) {
	  $str_qry = "select 
				ac.cc_id, 
				ar.debit_balance,
				ar.credit_balance,
				ar.profile_id,
				ar.account_comments,
				ac.account_number,
				ac.account_name
			FROM 
				account_cc ac
			LEFT JOIN ".Monthalize('account_record')." ar 
			ON	ac.cc_id = ar.cc_id
			WHERE
				ac.cc_id = ".$_GET["id"];
	  $qry_account= new Query($str_qry);

  } else {
	  $str_qry = "select 
				ac.cc_id, 
				ar.debit_balance,
				ar.credit_balance,
				ar.profile_id,
				ar.account_comments,
				ac.account_number,
				ac.account_name
			FROM 
				account_cc ac
			LEFT JOIN ".Monthalize('account_record')." ar 
			ON	ac.cc_id = ar.cc_id
			WHERE
				ac.cc_id = ".$_GET["cc_id"];
//	  echo $str_qry;
	  $qry_account= new Query($str_qry);

  }
  $form->addElement('header', '', 'View/Modify '.$qry_account->FieldByName('account_name'));
  $form->addElement('hidden', 'cc_id', $qry_account->FieldByName('cc_id'));
  $form->addElement('static', 'account_number', 'Account Number:');
  $form->addElement('text', 'account_comments', 'Comments:');
//  $form->addElement('static', 'debit_balance', 'Debit Balance:');
//  $form->addElement('static', 'credit_balance', 'Credit Balance:');
  $form->addElement('hidden', 'debit_balance', 'Debit Balance:');
  $form->addElement('hidden', 'credit_balance', 'Credit Balance:');

  $qry = new Query("select * from account_profile order by profile_name");
  $arr_profile_list=array();
  for ($i=0;$i<$qry->RowCount();$i++) {
    $arr_profile_list[$qry->FieldByName("profile_id")] =  $qry->FieldByName("profile_name");

  	$qry->Next();
  }
  $profile_select =& $form->addElement('select', 'profile_id', 'Profile:', $arr_profile_list);



//====================
  $form->applyFilter('__ALL__', 'trim');

// Adds some validation rules

  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Save');
  $buttons[] = &HTML_QuickForm::createElement('button', 'ibutTest', 'Close', array('onClick' => "window.close();"));
  $form->addGroup($buttons, null, null, '&nbsp;', false);


//=================== load variables
//
// Fills with some defaults values
//
  if (!empty($_GET['id'])) {
	$form->setDefaults(array(
		'cc_id'  => ($qry_account->FieldByName("cc_id")),
		'profile_id'  => ($qry_account->FieldByName("profile_id")),
		'account_comments'  => ($qry_account->FieldByName("account_comments")),
		'account_number'  => ($qry_account->FieldByName("account_name")),
		'debit_balance'  => ($qry_account->FieldByName("debit_balance")+0),
		'credit_balance'  => ($qry_account->FieldByName("credit_balance")+0)

		)
	);
	$profile_select->setValue($qry_account->FieldByName("profile_id"));

  }  else {

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
	$stDel = "DELETE FROM " . Monthalize('account_record') . " WHERE cc_id=".$values['cc_id'];

    	$stUpdate = "
		INSERT INTO ".Monthalize('account_record')." (
			cc_id,
			profile_id,
			account_comments,
			debit_balance,
			credit_balance
			)
			VALUES (
			".$values['cc_id'].",
			".$values['profile_id'].",
			'".addslashes($values['account_comments'])."',
			".$values['debit_balance'].",
			".$values['credit_balance']."
			)
			";
//	echo $stDel;
//	echo $stUpdate;
			//die($stUpdate);
			if (!$existQuery = new Query($stDel)) {
				$msg='Unable to delete record';
			} else 
 				if (!$existQuery->Query($stUpdate)) {
				  $msg = "There was an error while trying to add the record! ".$existQuery->GetErrorMessage();
				}

			$existQuery->Free();
			$parentRefresh=true;



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
