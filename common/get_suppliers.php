<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

    function get_suppliers($str_city) {

	if ($str_city == '__BLANK')
	    $qry = new Query("
		    SELECT supplier_id, supplier_name
		    FROM stock_supplier
		    WHERE supplier_city = ''
				AND is_active = 'Y'
		    ORDER BY supplier_name
	    ");
	else 
	    $qry = new Query("
		    SELECT supplier_id, supplier_name
		    FROM stock_supplier
		    WHERE supplier_city = '".$str_city."'
				AND is_active = 'Y'
		    ORDER BY supplier_name
	    ");

        $str_retval = '';
        for ($i=0; $i<$qry->RowCount(); $i++) {
            $str_retval .= $qry->FieldByName('supplier_id')."^".$qry->FieldByName('supplier_name')."|";
            $qry->Next();
        }
        $str_retval = substr($str_retval, 0, strlen($str_retval)-1);
        
        return $str_retval;
    }
    
    
    if (!empty($_GET['live'])) {
        if (!empty($_GET['city'])) {
            if ($_GET['live'] == 1)
                echo get_suppliers($_GET['city']);
            die();
        }
        else {
            die("nil");
        }
    }
?>