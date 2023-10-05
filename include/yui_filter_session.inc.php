<?php
	$str_filter = '';
	if (IsSet($_POST['filter'])) {
		$str_filter = $_POST['filter'];
		$_SESSION['current_filter_value'] = $str_filter;
	}
	elseif (IsSet($_SESSION['current_filter_value'])) {
		$str_filter = $_SESSION['current_filter_value'];
	}
	
	
	$str_field = $arr_fields[0]['yui_fieldname'];
	if (IsSet($_POST['field'])) {
		$str_field = $_POST['field'];
		$_SESSION['current_filter_field']=$str_field;
	}
	elseif (IsSet($_SESSION['current_filter_field'])) {
		$str_field = $_SESSION['current_filter_field'];
	}
	
	/*
		if the session field is found in array arr_fields
		set the filter value, otherwise not
	*/
	$bool_found = false;
	foreach ($arr_fields as $key=>$value) {
		$str_temp = $arr_fields[$key]['alias'].".".$arr_fields[$key]['fieldname'];
		if ($str_temp == $_SESSION['current_filter_field']) {
			$bool_found = true;
			break;
		}
	}
	if (!$bool_found)
		$str_filter = '';
	
	$str_mode = 'contains';
	if (IsSet($_POST['filter_mode'])) {
		$str_mode = $_POST['filter_mode'];
		$_SESSION['current_filter_mode']=$str_mode;
	}
	elseif (IsSet($_SESSION['current_filter_mode'])) {
		$str_mode = $_SESSION['current_filter_mode'];
	}
	
?>