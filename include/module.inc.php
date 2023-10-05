<?
require_once('db.inc.php');

if (!class_exists('Module')) {

abstract class Module {
	var $str_module_name;
	var $str_module_folder;
	var $int_module_id;
	var $arr_storerooms;
	var $str_active_link;
	function Module() {
		/*
			set module permissions
		*/
		$str_query = "
			SELECT *
			FROM user_permissions
			INNER JOIN module ON (module.module_id = user_permissions.module_id)
			WHERE user_id = ".$_SESSION["int_user_id"]."
				AND module.module_id = ".$this->int_module_id;

		$qry_base = new Query($str_query);
	
		for ($i=0;$i<$qry_base->RowCount();$i++) {
			$this->arr_storerooms[$qry_base->FieldByName("storeroom_id")] = $qry_base->FieldByName("access_level");
			$qry_base->Next();
		}
		unset($qry_base);
	}
	
	abstract function createMonth($f_month, $f_year);
	abstract function monthExists($f_month, $f_year);
	
	function buildMenu($f_int_selected) {
		// only build if the current storeroom has permissions
		if (@$this->arr_storerooms[$_SESSION['int_current_storeroom']] >= ACCESS_READ) {
	
			if ($f_int_selected==$this->int_module_id) {
				echo "<a href='javascript:parent.frames[\"content\"].document.location=\"".$this->str_active_link."\";document.location=\"title.php?int_module_selected=".$this->int_module_id."\";' class='mainmenu_buttonselected'>".$this->str_module_name."</a>";
				
		
			} else echo "<a href='javascript:parent.frames[\"content\"].document.location=\"".$this->str_active_link."\";document.location=\"title.php?int_module_selected=".$this->int_module_id."\";' class='mainmenu_button'>".$this->str_module_name."</a>";
		} 
	
	}
	
	function buildSubMenu() {
	}
}
}
?>