<?PHP
require_once 'config.inc.php';
require_once 'Parser.php';

class myParser extends XML_Parser {
	var $counter = 0;
	var $state = '';
	var $arr_data = array();
	var $arr_attributes = array();
	var $element_identifier='';
	
	function myParser($attributes, $element_identifier) {
		$this->arr_attributes = $attributes;
		$this->element_identifier = $element_identifier;
		
		parent::XML_Parser();
	}

	/**
	* handle start element
	*
	* @access   private
	* @param    resource    xml parser resource
	* @param    string      name of the element
	* @param    array       attributes
	*/
	function startHandler($xp, $name, $attribs) {
		foreach ($this->arr_attributes as $attribute) {
			if ($name == $attribute)
				$this->arr_data[$this->counter][$name] = $attribs[$name];
			else
				$this->state = $name;
		}
	}

	/**
	* handle start element
	*
	* @access   private
	* @param    resource    xml parser resource
	* @param    string      name of the element
	* @param    array       attributes
	*/
	function endHandler($xp, $name) {
		$this->state='';
		
		if ($name == $this->element_identifier) {
			$this->counter++;
		}
	}

	function getData() {
		return $this->arr_data;
	}
}

?>