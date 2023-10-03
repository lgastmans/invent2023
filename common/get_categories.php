<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    

    function get_categories($int_type) {
        if ($int_type == 'ALL')
            $qry = new Query("
                SELECT *
                FROM stock_category
                ORDER BY category_description
            ");
        else if ($int_type == '1')
            $qry = new Query("
                SELECT *
                FROM stock_category
                WHERE is_perishable = 'Y'
                ORDER BY category_description
            ");
        else if ($int_type == '2')
            $qry = new Query("
                SELECT *
                FROM stock_category
                WHERE is_perishable = 'N'
                ORDER BY category_description
            ");

        $str_retval = '';
        for ($i=0; $i<$qry->RowCount(); $i++) {
            $str_retval .= $qry->FieldByName('category_id')."^".$qry->FieldByName('category_description')."|";
            $qry->Next();
        }
        $str_retval = substr($str_retval, 0, strlen($str_retval)-1);
        
        return $str_retval;
    }
    
    
    if (!empty($_GET['live'])) {
        if (!empty($_GET['type'])) {
            if ($_GET['live'] == 1)
                echo get_categories($_GET['type']);
            die();
        }
        else {
            die("nil");
        }
    }
?>