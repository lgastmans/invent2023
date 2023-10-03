<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once 'HTML/QuickForm.php';

	$result_user=new Query("SELECT po_prediction_method
		FROM user
		WHERE (user_id=".$_SESSION['int_user_id'].")");

	$form =& new HTML_QuickForm('frmTest', 'get');
	$arr_options[0] = 'None';
	$arr_options[1] = 'Previous month only';
	$arr_options[2] = 'Previous and current month';
	$arr_options[3] = 'Current month only';
	$select =& $form->addElement('select', 'intPredictionMethod', 'Select : ', $arr_options);
	$select->setSize(4);

	$buttons[] = &HTML_QuickForm::createElement('submit', 'action', 'save');
	$buttons[] = &HTML_QuickForm::createElement('button', 'action', 'cancel', array('onClick' => "window.close();"));
	$form->addGroup($buttons, null, null, '&nbsp;', false);

	if (empty($_GET["action"])) {
		$select->setValue($result_user->FieldByName('po_prediction_method'));
	}

	if (IsSet($_GET["action"])) {
		if ($_GET["action"] == "save") {
			if ($form->validate()) {
			// Form is validated, then processes the data
				$form->freeze();
				$form->process('saveForm', false);
			}
		}
	}

	$form->display();

	function saveForm($values) {

//		show_arr($values);
		$result_update = new Query("UPDATE user
			SET po_prediction_method = ".$values['intPredictionMethod']."
			WHERE (user_id=".$_SESSION['int_user_id'].")");

		$result_update->Free();

		echo "<script language=\"javascript\">\n";
		echo "window.opener.document.location=window.opener.document.location.href;\n";
		echo "window.close();\n";
		echo "</script>\n";

	}	// end function saveForm

?>