<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    
    $qry_update = new Query("
        SELECT *
        FROM account_pt_balances_2006_7
    ");
    
    $qry = new Query("
        SELECT *
        FROM account_pt_balances_2006_6
    ");
    
    for ($i=0;$i<$qry->RowCount();$i++) {
        $int_id = $qry->FieldByName('account_pt_balance_id');
        $opening_balance = $qry->FieldByName('opening_balance');
        
        $qry_update->Query("
            UPDATE account_pt_balances_2006_7
            SET opening_balance = $opening_balance,
                closing_balance = $opening_balance
            WHERE account_pt_balance_id = $int_id
        ");
        
        $qry->Next();
    }
?>