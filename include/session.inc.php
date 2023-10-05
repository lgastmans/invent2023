<?

/**
* 
* @version 	$Id: session.inc.php,v 1.4 2006/02/25 06:29:23 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Session Include
* @name  	session.inc.php
*/

/**
 * gets the access level a module based on session variables
 *
 * @param   string    module name to search for
 *
 */
 
//  if (!empty($bool_root_folder)) {
  
	require_once($str_application_path."include/module.inc.php");
	require_once($str_application_path."admin/module.inc.php");
	require_once($str_application_path."billing/module.inc.php");
	require_once($str_application_path."accounts/module.inc.php");
	require_once($str_application_path."stock/module.inc.php");
	require_once($str_application_path."pt_accounts/module.inc.php");
	require_once($str_application_path."storerooms/module.inc.php");
	require_once($str_application_path."clients/module.inc.php");
	require_once($str_application_path."settings/module.inc.php");
	require_once($str_application_path."books/module.inc.php");

	if (APPLICATION_PACKAGE > 1) {
		require_once($str_application_path."orders/module.inc.php");
	}

	if (APPLICATION_PACKAGE > 2) {
		require_once($str_application_path."purchase/module.inc.php");
	}

	/*
	function getModuleAccessLevel($str_module) {
		for ($i=0;$i<count($_SESSION['arr_modules']);$i++) {
			if (($_SESSION['arr_modules'][$i]->str_module_name == $str_module)) {
				return $_SESSION['arr_modules'][$i]->arr_storerooms[$_SESSION['int_current_storeroom']];
			}
		}
		return 0;
	}
	*/
	
	function getModuleAccessLevel($str_module) {
		for ($i=0;$i<count($_SESSION['arr_modules']);$i++) {
			if (($_SESSION['arr_modules'][$i]->str_module_name == $str_module)) {
				if (count($_SESSION['arr_modules'][$i]->arr_storerooms) > 0) {
					foreach ($_SESSION['arr_modules'][$i]->arr_storerooms as $key=>$value) {
						if ($key == $_SESSION['int_current_storeroom'])
							return $_SESSION['arr_modules'][$i]->arr_storerooms[$_SESSION['int_current_storeroom']];
					}
				}
			}
		}
		return 0;
	}
	
	function getStoreRoomList() {
		$arr_storeroom_list=array();
		$qry=new Query("select * from stock_storeroom");
		for ($i=0;$i<$qry->RowCount();$i++) {
	/*		for ($j=0;$j<count($_SESSION['arr_modules']);$j++) {
	//			echo "Modules : ".($_SESSION['arr_modules'][$j]->str_module_name;
				if ($_SESSION['arr_modules'][$j]->arr_storerooms[$i]>=ACCESS_READ) {
	*/
					$arr_storeroom_list[$qry->FieldByName('storeroom_id')] = $qry->FieldByName('description');
	//			}
	//		}
			$qry->Next();
		}
		return $arr_storeroom_list;
	}

	session_start();

	if (!isset($_SESSION['arr_total_qty']))
		$_SESSION['arr_total_qty'] = array();
	
	/*
		filter settings for the YUI grids
	*/
	if (!IsSet($_SESSION['current_filter_value']))
		$_SESSION['current_filter_value']='';
	
	if (!IsSet($_SESSION['current_filter_field']))
		$_SESSION['current_filter_field']='';
	
	if (!IsSet($_SESSION['current_filter_mode']))
		$_SESSION['current_filter_mode']='contains';
	
	if (!IsSet($_SESSION['invent_is_new_financial_year'])) {
		$_SESSION['invent_is_new_financial_year'] = false;
	}
	
	if ((!IsSet($_SESSION['bool_logged_in'])) || ($_SESSION['bool_logged_in'] != true)) {
		echo "<script language='javascript'>\n";
		echo "top.document.location='http://".$_SERVER['SERVER_NAME'].$arr_invent_config['application']['folder']."login.php'\n";
		echo "</script>";

		//header("http://".$_SERVER['SERVER_NAME'].$arr_invent_config['application']['folder']."login.php");
		//header("Location: ".$arr_invent_config['application']['folder']."login.php");
		exit;
	}
	
	if (!empty($str_cur_module)) {
		if (getModuleAccessLevel($str_cur_module)<1) {
			die ("<html><body>You don't have access to this module.</body></html>");
		}
	}

	if ($_SESSION['str_user_font_size'] == 'small') {
		$str_class_header = "headertext_small";
		$str_class_input = "inputbox100_small";
		$str_class_input200 = "inputbox200_small";
		$str_class_input300 = "inputbox300_small";
		$str_class_input400 = "inputbox400_small";
		$str_class_select = "select_small";
		$str_class_span = "spantext_small";
		$str_class_list_box = "listbox_small";
		$str_class_list_header = "listheader_small";
		$str_class_total = "bill_total_small";
		
		$str_normaltext = "normaltext_small";
	}
	else if ($_SESSION['str_user_font_size'] == 'standard') {
		$str_class_header = "headertext";
		$str_class_input = "inputbox100";
		$str_class_input200 = "inputbox200";
		$str_class_input300 = "inputbox300";
		$str_class_input400 = "inputbox400";
		$str_class_select = "select";
		$str_class_span = "spantext";
		$str_class_list_box = "listbox";
		$str_class_list_header = "listheader";
		$str_class_total = "bill_total";
		
		$str_normaltext = "normaltext";
	}
	else if ($_SESSION['str_user_font_size'] == 'large') {
		$str_class_header = "headertext_large";
		$str_class_input = "inputbox100_large";
		$str_class_input200 = "inputbox200_large";
		$str_class_input300 = "inputbox300_large";
		$str_class_input400 = "inputbox400_large";
		$str_class_select = "select_large";
		$str_class_span = "spantext_large";
		$str_class_list_box = "listbox_large";
		$str_class_list_header = "listheader_large";
		$str_class_total = "bill_total_large";
		
		$str_normaltext = "normaltext_large";
	}
	else {
		$str_class_header = "headertext";
		$str_class_input = "inputbox100";
		$str_class_input200 = "inputbox200";
		$str_class_input300 = "inputbox300";
		$str_class_input400 = "inputbox400";
		$str_class_select = "select";
		$str_class_span = "spantext";
		$str_class_list_box = "listbox";
		$str_class_list_header = "listheader";
		$str_class_total = "bill_total";
		
		$str_normaltext = "normaltext";
	}

	if ($_SESSION['str_user_color_scheme'] == 'standard')
		$str_css_filename = 'bill_styles.css';
	else if ($_SESSION['str_user_color_scheme'] == 'blue')
		$str_css_filename = 'bill_styles_blue.css';
	else if ($_SESSION['str_user_color_scheme'] == 'purple')
		$str_css_filename = 'bill_styles_purple.css';
	else if ($_SESSION['str_user_color_scheme'] == 'green')
		$str_css_filename = 'bill_styles_green.css';
	else
		$str_css_filename = 'bill_styles.css';

	
	if (ini_get('register_globals'))
	{
	    foreach ($_SESSION as $key=>$value)
	    {
	        if (isset($GLOBALS[$key]))
	            unset($GLOBALS[$key]);
	    }
	}	

	
?>