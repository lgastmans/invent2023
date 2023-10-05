<?
/**
* Classes for creation of grids
* This package contains the basic building blocks for creating complex user interfaces
* that displays information in a grid-like manner, with advanced sorting and filtering
* options.
* It's very easy to set up and use.
* @package CynergyControls
*/
/**
* Class that contains the basic column elements for the main Grid class
*
* The column class is the basic class that is used by Grid to store column
* information
* @package Grid
*/
class GridColumn {
/**
* column header text
* @access public
* @var string
*/
	var $str_column_header = "Column Header";
/**
* field to display contents of
* @access public
* @var string
*/
	var $str_column_field = "db_field";
/**
* field type (string, currency, numeric, boolean, date)
* @access public
* @var string
*/
	var $str_column_field_type = "string";
/**
* whether or not field can be filtered (see Grid class)
* @access public
* @var string
*/
	var $bool_column_filtered = false;
/**
* whether or not column is ordered
* @access public
* @var string
*/
	var $bool_column_ordered = false;
/**
* direction of ordering (blank or desc for descending)
* @access public
* @var string
*/
	var $str_order_direction = '';
/**
* width of table column (optional)
* @access public
* @var string
*/
	var $str_column_width='';
/**
* callback function (optional);
* @access public
* @var string
*/
	var $fn_callback;

/**
 * GridColumn Constructor
 *
 * @param   string    the header text for the column
 * @param   string    the mysql field name of column data
 * @param   string    the field type (string, currency, number, date)
 * @param   string    whether the field can be filtered (see GridForm)
 * @param   string    the width of the column
 *
 */
	function GridColumn($f_str_header, $f_str_field, $f_str_field_type,$f_bool_filtered=true,$f_str_width="",$f_callback=null) {
		$this->str_column_header = $f_str_header;
		$this->str_column_field = $f_str_field;
		$this->str_column_field_type = $f_str_field_type;
		$this->bool_column_filtered = $f_bool_filtered;
		$this->str_column_width = $f_str_width;
		$this->fn_callback = $f_callback;
	}

}
/**
* Class for filtering options in the dataset
*
* The filter class takes care of filtering (the WHERE part of the SQL clause).
* it builds up the sql code in the processFilter function.
* @package Grid
*/
class GridFilter {
/**
* field to filter
* @access public
* @var string
*/
	var $str_filter_field = "";
/**
* comparison operator.
* - <i>equals</i>	If the field contents is exactly equal to the value
* - <i>starts with</i>	If the field starts with the value
* - <i>contains</i>	If the field contains the value
* - <i>ends with</i>	If the field ends with the value
* - <i>less than</i>	If the field is less than the value
* - <i>greater than</i>	If the field is greater than the value
* @access public
* @var string
*/
	var $str_filter_comparison = "equals";
/**
* value of the filter operator
* @access public
* @var string
*/
	var $str_filter_value = "";
/**
* filter type
* - <i>string</i>	Uses the ' operator in the comparison since data type is alphanmeric
* - <i>number</i>	Treats comparison as numeric
* - <i>bool</i>	Treats comparison as boolean (Y or N)
* @access public
* @var string
*/
	var $str_filter_type = "string";	// string or number or bool

/**
* filter relation
* - <i>and</i>	Will be anded with previous condition
* - <i>or</i>	will be or'ed with previous condition
* @access public
* @var string
*/
	var $str_filter_relation = "string";	// string or number or bool

	function GridFilter($f_str_field, $f_str_comparison, $f_str_value, $f_str_field_type,$f_str_field_relation) {
		$this->str_filter_field = $f_str_field;
		$this->str_filter_field_type = $f_str_field_type;
		$this->str_filter_comparison = $f_str_comparison;
		$this->str_filter_value = $f_str_value;
		$this->str_filter_relation= $f_str_field_relation;

	}
	function processFilter() {
		$str_res = "";
		
		// fix for 'default' value
		
		if ($this->str_filter_comparison=="equals") {
			if ($this->str_filter_type=="string") {
				$str_res = $this->str_filter_field." = '".$this->str_filter_value."' ";
			} else if ($this->str_filter_type=="number") {
				$str_res = $this->str_filter_field." = ".$this->str_filter_value." ";
			}
		} else if ($this->str_filter_comparison=="less than") {
			if ($this->str_filter_type=="string") {
				$str_res = $this->str_filter_field." < '".$this->str_filter_value."' ";
			} else if ($this->str_filter_type=="number") {
				$str_res = $this->str_filter_field." < ".$this->str_filter_value." ";
			}
		} else if ($this->str_filter_comparison=="greater than") {
			if ($this->str_filter_type=="string") {
				$str_res = $this->str_filter_field." > '".$this->str_filter_value."' ";
			} else if ($this->str_filter_type=="number") {
				$str_res = $this->str_filter_field." > ".$this->str_filter_value." ";
			}

		} else if ($this->str_filter_comparison=="starts with") {
			if ($this->str_filter_type=="string") {
				$str_res = $this->str_filter_field." LIKE '".$this->str_filter_value."%' ";
			} else $str_res =  "";

		} else if ($this->str_filter_comparison=="contains") {
			if ($this->str_filter_type=="string") {
				$str_res = $this->str_filter_field." LIKE '%".$this->str_filter_value."%' ";
			} else $str_res = "";

		} else if ($this->str_filter_comparison=="ends with") {
			if ($this->str_filter_type=="string") {
				$str_res = $this->str_filter_field." LIKE '%".$this->str_filter_value."' ";
			} else $str_res = "";

		}
		return $str_res;
	}
}

require_once("db.inc.php");


//
//  Grid
//
//  manages a grid
//
class Grid {
	var $arr_grid_columns;	// list of columns to display
	var $arr_grid_ordering;	// list of ordered columns
	var $arr_grid_filtering;	// filter conditions for the query

	var $arr_custom_parameters = array(); 	// lisf of custom parameters to maintain

	var $qry_data_source;		// data source for display

	var $int_page_size=50;
	var $int_page_number=0;

	var $int_month_days=14;

	var $str_row_style_even="";
 	var $str_row_style_odd="";
	var $str_query_string="";
	var $str_query_string_prepared="";

	var $bool_query_prepared=false;
	var $bool_simple_ordering=true;

	var $str_key_field="";
	var $str_select_function="";
	var $str_submit_url="" ;
	var $str_image_path="../images/" ;
	var $str_web_root="/invent/";
//CYNERGY:	var $str_web_root="/pourtous/html/" ;
//NPT:		var $str_web_root="/pourtous/";
	var $str_deleted_field="";
	var $bool_show_deleted=false;
	// colors
	var $str_color_header="#808080";
	var $str_color_header_ordered="#E0E080";

	var $str_color_row_even="#deecfb";
	var $str_color_row_odd="#eff7ff";
	var $str_color_link="#aaccee";
	var $str_color_over="#CCFFCC";

	var $str_header_class='headertext';
	var $str_row_class='normaltext';
	var $str_row_class_deleted='deletedtext';
	var $b_debug = false;
	var $b_print = false;
	var $b_export = false;

	function Grid() {
		$this->qry_data_source = null;
//		$this->str_submit_url = $_SERVER["request_uri"];
	}

	function addColumn($f_str_header, $f_str_field, $f_str_field_type, $f_bool_filtered=false, $f_str_width='',$f_callback='') {
		$this->arr_grid_columns[]= new GridColumn($f_str_header, $f_str_field, $f_str_field_type, $f_bool_filtered, $f_str_width,$f_callback);
	}

	function getColumnByName($f_str_name) {
		for ($i=0; $i < count($this->arr_grid_columns); $i++) {
			if ($this->arr_grid_columns[$i]->str_column_header == $f_str_name)
				return $this->arr_grid_columns[$i];
		}
		return null;
	}

	function getColumnByFieldName($f_str_name) {
		for ($i=0; $i < count($this->arr_grid_columns); $i++) {
			if ($this->arr_grid_columns[$i]->str_column_field == $f_str_name)
				return $this->arr_grid_columns[$i];
		}
		return null;
	}

	function getColumn($f_int_pos) {
		return $this->arr_grid_columns[$f_int_pos];
	}

	function setQuery($f_str_query) {
		$this->str_query_string = $f_str_query;
	}

	function setPageSize($f_page_size) {
		$this->int_page_size = $f_page_size;
	}

	function setMonthDays($f_month_days) {
		$this->int_month_days = $f_month_days;
	}

	function setCurrentPage($f_page_number) {
		$this->int_page_number = $f_page_number;
	}

	function setImagePath($f_str_path) {
		$this->str_image_path = $f_str_path;
	}

	function setWebRoot($f_str_path) {
		$this->str_web_root = $f_str_path;
	}

	function setDeletedField($f_str_deleted) {
		$this->str_deleted_field = $f_str_deleted;
	}

	function setShowDeleted($f_bool_show_deleted) {
		$this->bool_show_deleted = $f_bool_show_deleted;
	}

	function setSubmitURL($f_str_url) {
		$this->str_submit_url = $f_str_url;
	}

	function addCustomParameter($f_str_param) {
		$this->arr_custom_parameters[$f_str_param]='';
	}

	function addOrder($f_str_column,$f_direction) {
		$element = $this->getColumnByFieldName($f_str_column);

		if ($element) {
			if ($element->bool_column_ordered==false) {
				$element->bool_column_ordered = true;
				$this->arr_grid_ordering[] = $f_str_column;
				$element->int_order = count($this->arr_grid_ordering);
				$element->str_order_direction = $f_direction;
			}
		}
	}

	function addFilter($f_str_field, $f_str_comparison, $f_str_value, $f_str_filter_type,$f_str_filter_relation='and') {
//		$element = $this->getColumnByFieldName($f_str_field);
//		if ($element) {
			$this->arr_grid_filtering[] = new GridFilter($f_str_field, $f_str_comparison, $f_str_value, $f_str_filter_type,$f_str_filter_relation);
//		} else die("unable to find column $f_str_field");
	}

	function addUniqueFilter($f_str_field, $f_str_comparison, $f_str_value, $f_str_filter_type,$f_str_filter_relation='and') {
//		$element = $this->getColumnByFieldName($f_str_field);
//		if ($element) {
		$b_found = false;
		for ($i=0;$i<count($this->arr_grid_filtering);$i++) {
			if ($this->arr_grid_filtering[$i]->str_filter_field==$f_str_field)
				$b_found=true;
		}
		if (!$b_found) {
			$this->arr_grid_filtering[] = new GridFilter($f_str_field, $f_str_comparison, $f_str_value, $f_str_filter_type,$f_str_filter_relation);
		}
//		} else die("unable to find column $f_str_field");
	}

	function prepareQuery() {
		if ($this->bool_query_prepared)
			return false;

		$this->str_query_string_prepared = $this->str_query_string;
		//
		// set up filtering options
		//
		if (count($this->arr_grid_filtering)>0) {
			$this->str_query_string_prepared .= " WHERE ";

			// special processing for 'or' queries
			if (count($this->arr_grid_filtering)>1){
				if ($this->arr_grid_filtering[1]->str_filter_relation=='or') {
					$this->str_query_string_prepared .= "(";
				}
			}
			$this->str_query_string_prepared .= $this->arr_grid_filtering[0]->processFilter();

			for ($i=1; $i<count($this->arr_grid_filtering); $i++) {
				if (($this->arr_grid_filtering[$i]->str_filter_value<>"") && ($this->arr_grid_filtering[$i]->str_filter_value<>"-")) {
					if ($this->arr_grid_filtering[$i]->str_filter_relation=='and') {
						$this->str_query_string_prepared .= " AND ".$this->arr_grid_filtering[$i]->processFilter();
					} else {
						$this->str_query_string_prepared .= " OR ".$this->arr_grid_filtering[$i]->processFilter().")";
					}	
				}
			}
		}
		//
		// special processing for deleted fields
		//
		if (!empty($this->str_deleted_field)) {
			if (!$this->bool_show_deleted) {
				if (count($this->arr_grid_filtering)==0) {
					$this->str_query_string_prepared .= " WHERE ". $this->str_deleted_field."='N'";
				} else {
					$this->str_query_string_prepared .= " AND ". $this->str_deleted_field."='N'";
				}
			}
		}

		//
		// set up ordering options
		//
		if (count($this->arr_grid_ordering)>0) {
			$element = $this->getColumnByFieldName($this->arr_grid_ordering[0]);
			$this->str_query_string_prepared .= " ORDER BY ".$element->str_column_field." ".$element->str_order_direction." ";

			for ($i=1; $i < count($this->arr_grid_ordering); $i++) {
				$element = $this->getColumnByFieldName($this->arr_grid_ordering[$i]);
				$this->str_query_string_prepared .= "," . $element->str_column_field." ".$element->str_order_direction." ";
			}

		}
//		echo $this->str_query_string_prepared;
		if ($this->b_debug) {
			echo "Debug: ".$this->str_query_string_prepared;
		}
		$this->qry_data_source = new Query($this->str_query_string_prepared);
		if ($this->qry_data_source) {
			if ($this->qry_data_source->b_error) die ($this->qry_data_source->GetErrorMessage()."<br>Original Query: <br>".$this->str_query_string_prepared);
			$this->bool_query_prepared = true;
			return true;
		} else return false;
	}

	function setOnClick($str_function_name, $str_key_field) {
		$this->str_select_function=$str_function_name;
		$this->str_key_field = $str_key_field;
	}

	function buildFormString($f_str_field) {
		$str_form_string="";
		//
		// ordering
		//
		if ($this->bool_simple_ordering) {
			if (count($this->arr_grid_ordering)>0) {
				$element=$this->getColumnByFieldName($this->arr_grid_ordering[0]);
				$str_form_string = "<input type='hidden' name='order0' value='" . $this->arr_grid_ordering[0] . " " . $element->str_order_direction."'>";
			}
		} else {
			// TODO
		}
		//
		// filtering
		//

		for ($i=0; $i < count($this->arr_grid_filtering); $i++) {
			$element = $this->arr_grid_filtering[$i];
//			if ((!is_numeric($f_str_field)) || ((is_numeric($f_str_field)) && ($i <> $f_str_field))) {
      if ($i <> $f_str_field) {
				$str_form_string .= "<input type='hidden' name='ff$i' value= '".$element->str_filter_field . "'><input type='hidden' name='fc$i' value='". $element->str_filter_comparison."'><input type='hidden' name='fv$i' value='".$element->str_filter_value . "'><input type='hidden' name='ft$i' value='".$element->str_filter_type."'>";
			}
		}

		$str_form_string.="<input type=hidden name='page' value='".$this->int_page_number."'>";
		if ($f_str_field=='pagesize') {
		} else {
			$str_form_string.="<input type=hidden name='pagesize' value='".$this->int_page_size."'>";
		}

		if (!empty($this->str_deleted_field)) {
			$str_form_string.="<input type=hidden name='showdeleted' value='".($this->bool_show_deleted?'Y':'N')."'>";
		}
		if ($f_str_field=='monthdays') {
		} else {
			$str_form_string.="<input type=hidden name='monthdays' value='".$this->int_month_days."'>";
		}


		//
		// custom parameters
		//
		reset ($this->arr_custom_parameters);
		foreach ($this->arr_custom_parameters as $key=>$value) {
			$str_form_string .= "<input type='hidden' name='$value' value='$key'>";
		}

		return $str_form_string;

	}


	function buildQueryString($f_str_style='order',$f_str_field='') {
		$str_query_string="";
		if ($f_str_style=='page') {
			$str_query_string.="page=".$f_str_field;
		} else {
			$str_query_string.="page=".$this->int_page_number;
		}
		if ($f_str_style=='pagesize') {
			$str_query_string.="&pagesize=".$f_str_field;
		} else {
			$str_query_string.="&pagesize=".$this->int_page_size;
		}
		if ($f_str_style=='monthdays') {
			$str_query_string.="&monthdays=".$f_str_field;
		} else {
			$str_query_string.="&monthdays=".$this->int_month_days;
		}

		if (!empty($this->str_deleted_field)) {
			$str_query_string.="&showdeleted=".($this->bool_show_deleted?'Y':'N');
		}

		//
		// ordering
		//
		if ($this->bool_simple_ordering) {
			if ($f_str_style=='order') {
				$str_query_string .= '&order0='.$f_str_field;
			} else {
				if (count($this->arr_grid_ordering)>0) {
					$str_query_string .= '&order0='.$this->arr_grid_ordering[0].'+'.$this->getColumnByFieldName($this->arr_grid_ordering[0])->str_order_direction;
				}
			}
		} else {
			for ($i=0; $i < count($this->arr_grid_ordering); $i++) {
				if (!empty($str_query_string)) $str_query_string.="&";
				$str_query_string .= "order$i=".$this->arr_grid_ordering[$i];
				$element = $this->getColumnByFieldName($this->arr_grid_ordering[$i]);
				if ($element->str_order_direction=="desc") {
					$str_query_string .= "+desc";
				}
			}
		}
		//
		// filtering
		//
		for ($i=0; $i < count($this->arr_grid_filtering); $i++) {
			if (!empty($str_query_string)) $str_query_string.="&";
			$element = $this->arr_grid_filtering[$i];
			$str_query_string .= "ff$i=".$element->str_filter_field . "&fc$i=". $element->str_filter_comparison."&fv$i=".$element->str_filter_value . "&ft$i=".$element->str_filter_type;
		}
		//
		// custom parameters
		//
		reset ($this->arr_custom_parameters);
		foreach ($this->arr_custom_parameters as $key=>$value) {
			$str_query_string .= "&".$key."=".$value;
		}

		return $str_query_string;
	}

	function drawGrid() {
		$this->prepareQuery();
		//
		//  javascript code
		//
		$str_javascript=<<<_HTML
<script language='javascript'>
/**
 * This array is used to remember mark status of rows in browse mode
 */
var marked_row = new Array;
var selected_Row;
var selected_RowNum;
var selected_RowColor;

/**
 * Sets/unsets the pointer and marker in browse mode
 *
 * @param   object    the table row
 * @param   interger  the row number
 * @param   string    the action calling this script (over, out or click)
 * @param   string    the default background color
 * @param   string    the color to use for mouseover
 * @param   string    the color to use for marking a row
 *
 * @return  boolean  whether pointer is set or not
 */
function setPointer(theRow, theRowNum, theAction, theDefaultColor, thePointerColor, theMarkColor)
{
    var theCells = null;

    if ((theRow != selected_Row) && (theAction=="click")) {
//      alert(selected_Row);

      if (typeof(selected_Row) == "object") {
			// 2. Gets the current row and exits if the browser can't get it
		if (typeof(document.getElementsByTagName) != 'undefined') {
			theCells = selected_Row.getElementsByTagName('td');
		}
		else if (typeof(selectedRow.cells) != 'undefined') {
			theCells = selected_Row.cells;
		}
		else {
			return false;
		}

		// 3. Gets the current color...
		var rowCellsCnt  = theCells.length;
		var domDetect    = null;
		var currentColor = null;
		var newColor     = null;
		// 3.1 ... with DOM compatible browsers except Opera that does not return
		//         valid values with "getAttribute"
		if (typeof(window.opera) == 'undefined'
			&& typeof(theCells[0].getAttribute) != 'undefined') {
			currentColor = theCells[0].getAttribute('bgcolor');
			domDetect    = true;
		}
		// 3.2 ... with other browsers
		else {
			currentColor = theCells[0].style.backgroundColor;
			domDetect    = false;
		} // end 3

		newColor                     = selected_RowColor;
		marked_row[selected_RowNum] = null;

		// 5. Sets the new color...
		if (newColor) {
			var c = null;
			// 5.1 ... with DOM compatible browsers except Opera
			if (domDetect) {
			for (c = 0; c < rowCellsCnt; c++) {
				theCells[c].setAttribute('bgcolor', newColor, 0);
			} // end for
			}
			// 5.2 ... with other browsers
			else {
			for (c = 0; c < rowCellsCnt; c++) {
				theCells[c].style.backgroundColor = newColor;
			}
			}
		} // end 5


      }
      selected_Row = theRow;
      selected_RowNum = theRowNum;
      selected_RowColor = theDefaultColor;

    };

    // 1. Pointer and mark feature are disabled or the browser can't get the
    //    row -> exits
    if ((thePointerColor == '' && theMarkColor == '')
        || typeof(theRow.style) == 'undefined') {
        return false;
    }

    // 2. Gets the current row and exits if the browser can't get it
    if (typeof(document.getElementsByTagName) != 'undefined') {
        theCells = theRow.getElementsByTagName('td');
    }
    else if (typeof(theRow.cells) != 'undefined') {
        theCells = theRow.cells;
    }
    else {
        return false;
    }

    // 3. Gets the current color...
    var rowCellsCnt  = theCells.length;
    var domDetect    = null;
    var currentColor = null;
    var newColor     = null;
    // 3.1 ... with DOM compatible browsers except Opera that does not return
    //         valid values with "getAttribute"
    if (typeof(window.opera) == 'undefined'
        && typeof(theCells[0].getAttribute) != 'undefined') {
        currentColor = theCells[0].getAttribute('bgcolor');
        domDetect    = true;
    }
    // 3.2 ... with other browsers
    else {
        currentColor = theCells[0].style.backgroundColor;
        domDetect    = false;
    } // end 3

    // 3.3 ... Opera changes colors set via HTML to rgb(r,g,b) format so fix it
    if (currentColor.indexOf("rgb") >= 0)
    {
        var rgbStr = currentColor.slice(currentColor.indexOf('(') + 1,
                                     currentColor.indexOf(')'));
        var rgbValues = rgbStr.split(",");
        currentColor = "#";
        var hexChars = "0123456789ABCDEF";
        for (var i = 0; i < 3; i++)
        {
            var v = rgbValues[i].valueOf();
            currentColor += hexChars.charAt(v/16) + hexChars.charAt(v%16);
        }
    }

    if (theAction == 'click') {

    }

    // 4. Defines the new color
    // 4.1 Current color is the default one
    if (currentColor == ''
        || currentColor.toLowerCase() == theDefaultColor.toLowerCase()) {
        if (theAction == 'over' && thePointerColor != '') {
            newColor              = thePointerColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
        }
    }
    // 4.1.2 Current color is the pointer one
    else if (currentColor.toLowerCase() == thePointerColor.toLowerCase()
             && (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])) {
        if (theAction == 'out') {
            newColor              = theDefaultColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
        }
    }
    // 4.1.3 Current color is the marker one
    else if (currentColor.toLowerCase() == theMarkColor.toLowerCase()) {
        if (theAction == 'click') {
            newColor              = theMarkColor;//(thePointerColor != '')
//                                  ? thePointerColor
//                                  : theDefaultColor;
            marked_row[theRowNum] = true;//(typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])
//                                  ? true
//                                  : null;
        }
    } // end 4

    // 5. Sets the new color...
    if (newColor) {
        var c = null;
        // 5.1 ... with DOM compatible browsers except Opera
        if (domDetect) {
            for (c = 0; c < rowCellsCnt; c++) {
                theCells[c].setAttribute('bgcolor', newColor, 0);
            } // end for
        }
        // 5.2 ... with other browsers
        else {
            for (c = 0; c < rowCellsCnt; c++) {
                theCells[c].style.backgroundColor = newColor;
            }
        }
    } // end 5

    return true;
} // end of the 'setPointer()' function
</script>
_HTML;
		echo $str_javascript;
		//
		//  draw header columns
		//
		echo "<table width='100%'><tr>";

		for ($i=0; $i < count($this->arr_grid_columns); $i++) {
			$element = $this->arr_grid_columns[$i];
			echo "<td class='".$this->str_header_class."' ";

			if (!empty($element->str_column_width)) echo "width='".$element->str_column_width."' ";
			//
			//  simple ordering versus multi-column ordering
			//
			if ($this->bool_simple_ordering) {
				echo "bgcolor='".$this->str_color_header."'>";
			} else {
				if ($element->bool_column_ordered)
		 			echo "bgcolor='".$this->str_color_header_ordered."'>";
				else
					echo "bgcolor='".$this->str_color_header."'>";
			}

			if ($element->bool_column_ordered) {
				if ($element->str_order_direction=="desc") {
					echo "<img src='".$this->str_image_path."up.gif'>&nbsp;";
					$str_direction='';
				} else {
					echo "<img src='".$this->str_image_path."down.gif'>&nbsp;";
					$str_direction='+desc';
				}
				echo "<a href='".$this->str_submit_url."?".$this->buildQueryString('order',$element->str_column_field.$str_direction)."'>";
				echo $element->str_column_header."</a></td>";
			} else {
				if ($this->bool_simple_ordering) {
					echo "<a href='".$this->str_submit_url."?".$this->buildQueryString('order',$element->str_column_field)."'>";
					echo $element->str_column_header."</a></td>";
				} else
				echo $element->str_column_header."</td>";
			}

		}
		echo "</tr>";
		//
		// jump to start in dataset
		//
		$int_num_rows=0;
 	  	$int_num_rows = $this->qry_data_source->RowCount();
		if ($this->int_page_number * $this->int_page_size < $int_num_rows)
			$this->qry_data_source->Seek($this->int_page_number * $this->int_page_size);
		//
		// calculate page size
		//
		if ($int_num_rows-($this->int_page_number*$this->int_page_size)<$this->int_page_size) {
			$int_num_recs = $int_num_rows-($this->int_page_number*$this->int_page_size);
		} else $int_num_recs = $this->int_page_size;

  		for ($int_row=0;$int_row < $int_num_recs; $int_row++) {
			$str_row_class = $this->str_row_class;
    			if ($int_row % 2==1)
				$str_bg_color=$this->str_color_row_even;
			else
				$str_bg_color=$this->str_color_row_odd;

			if ($this->bool_show_deleted) {
				if ($this->qry_data_source->FieldByName($this->str_deleted_field)=='Y') {
					$str_bg_color='#808080';
					$str_row_class=$this->str_row_class_deleted;

				}
			}
			$str_over_color = $this->str_color_over;
			$str_link_color = $this->str_color_link;

			echo "<tr onmousedown=\"setPointer(this, $int_row, 'click', '$str_bg_color', '$str_over_color', '$str_link_color');";
			if (!empty($this->str_select_function))
				echo $this->str_select_function . "('" . addslashes($this->qry_data_source->FieldByName($this->str_key_field)). "');";
			echo "\" onmouseover = \"setPointer(this, $int_row, 'over', '$str_bg_color', '$str_over_color', '$str_link_color');\" onmouseout=\"setPointer(this, $int_row, 'out', '$str_bg_color', '$str_over_color', '$str_link_color');\" class='".$str_row_class."'>";

			for ($int_col=0;$int_col < count($this->arr_grid_columns); $int_col++) {
				$element = $this->arr_grid_columns[$int_col];
				if ($element->str_column_field_type=="string") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color'>".$this->qry_data_source->FieldByName($element->str_column_field)."</td>";
				} else if ($element->str_column_field_type=="currency") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color' align=right>".sprintf("%0.2f",$this->qry_data_source->FieldByName($element->str_column_field))."</td>";
				} else if ($element->str_column_field_type=="number") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color' align=right>".sprintf("%0.3f",$this->qry_data_source->FieldByName($element->str_column_field))."</td>";
				} else if ($element->str_column_field_type=="boolean") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color'>";
					if ($this->qry_data_source->FieldByName($element->str_column_field)=="Y")
					echo "x";
					echo "</td>";
				} else if ($element->str_column_field_type=="date") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color' align=right>".FormatDate($this->qry_data_source->FieldByName($element->str_column_field))."</td>";
				} else if ($element->str_column_field_type=="custom") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color' >";
					call_user_func($element->fn_callback,$element->str_column_field, $this->qry_data_source);
					echo "</td>";
				}
			}
			echo "</tr>";
			$this->qry_data_source->Next();
		}
		echo "</table>";

	}


	function escape_string($str) {
		$str_retval = '';
		if (strpos($str, ";") > 0)
			$str_retval = "\"".$str."\"";
		else
			$str_retval = $str;
		return $str_retval;
	}

	function exportGrid() {

		header("Content-Type: application/text; name=list.txt");
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=list.txt");
		header("Expires: 0");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");

		$int_num_recs = $this->qry_data_source->RowCount();

		$str_current = '';
		for ($i=0; $i < count($this->arr_grid_columns); $i++) {
			$str_current .= $this->arr_grid_columns[$i]->str_column_header."\t";
		}
		$str_current = substr($str_current, 0, strlen($str_current) -2);
		echo $str_current."\n";

		for ($int_row=0;$int_row < $int_num_recs; $int_row++) {

			$str_current = '';

			for ($int_col=0;$int_col < count($this->arr_grid_columns); $int_col++) {

				$element = $this->arr_grid_columns[$int_col];
	
				if ($element->str_column_field_type=="string") {
					$str_current .= $this->escape_string($this->qry_data_source->FieldByName($element->str_column_field))."\t";
				} else if ($element->str_column_field_type=="currency") {
					$str_current .= sprintf("%0.2f",$this->qry_data_source->FieldByName($element->str_column_field))."\t";
				} else if ($element->str_column_field_type=="number") {
					$str_current .= sprintf("%0.3f",$this->qry_data_source->FieldByName($element->str_column_field))."\t";
				} else if ($element->str_column_field_type=="boolean") {
					$str_current .= $this->qry_data_source->FieldByName($element->str_column_field)."\t";
				} else if ($element->str_column_field_type=="date") {
					$str_current .= FormatDate($this->qry_data_source->FieldByName($element->str_column_field))."\t";
				} else if ($element->str_column_field_type=="custom") {
					$str_current .= $this->qry_data_source->FieldByName($element->str_column_field)."\t";
				}
			}

			$str_current = substr($str_current, 0, strlen($str_current) -2);

			echo $str_current."\n";

			$this->qry_data_source->Next();
		}
	}

	function drawPrintGrid() {
		$this->prepareQuery();
		//  draw header columns
		//
		echo "<table width='100%'><tr>";

		for ($i=0; $i < count($this->arr_grid_columns); $i++) {
			$element = $this->arr_grid_columns[$i];
			echo "<td class='".$this->str_header_class."' ";

			if (!empty($element->str_column_width)) echo "width='".$element->str_column_width."' ";
			//
			//  simple ordering versus multi-column ordering
			//
			if ($this->bool_simple_ordering) {
				echo "bgcolor='".$this->str_color_header."'>";
			} else {
				if ($element->bool_column_ordered)
		 			echo "bgcolor='".$this->str_color_header_ordered."'>";
				else
					echo "bgcolor='".$this->str_color_header."'>";
			}

			if ($element->bool_column_ordered) {
				if ($element->str_order_direction=="desc") {
					echo "<img src='".$f_grid->str_image_path."up.gif'>&nbsp;";
					$str_direction='';
				} else {
					echo "<img src='".$f_grid->str_image_path."down.gif'>&nbsp;";
					$str_direction='+desc';
				}
				echo "<a href='".$this->str_submit_url."?".$this->buildQueryString('order',$element->str_column_field.$str_direction)."'>";
				echo $element->str_column_header."</a></td>";
			} else {
				if ($this->bool_simple_ordering) {
					echo "<a href='".$this->str_submit_url."?".$this->buildQueryString('order',$element->str_column_field)."'>";
					echo $element->str_column_header."</a></td>";
				} else
				echo $element->str_column_header."</td>";
			}

		}
		echo "</tr>";
		//
		// jump to start in dataset
		//
 	  	$int_num_recs = $this->qry_data_source->RowCount();
		//
		// calculate page size
		//
  		for ($int_row=0;$int_row < $int_num_recs; $int_row++) {
			$str_row_class = $this->str_row_class;
    			if ($int_row % 2==1)
				$str_bg_color=$this->str_color_row_even;
			else
				$str_bg_color=$this->str_color_row_odd;

			if ($this->bool_show_deleted) {
				if ($this->qry_data_source->FieldByName($this->str_deleted_field)=='Y') {
					$str_bg_color='#808080';
					$str_row_class=$this->str_row_class_deleted;

				}
			}
			$str_over_color = $this->str_color_over;
			$str_link_color = $this->str_color_link;

			echo "<tr class='".$str_row_class."'>";

			for ($int_col=0;$int_col < count($this->arr_grid_columns); $int_col++) {
				$element = $this->arr_grid_columns[$int_col];
				if ($element->str_column_field_type=="string") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color'>".$this->qry_data_source->FieldByName($element->str_column_field)."</td>";
				} else if ($element->str_column_field_type=="currency") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color' align=right>".sprintf("%0.2f",$this->qry_data_source->FieldByName($element->str_column_field))."</td>";
				} else if ($element->str_column_field_type=="number") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color' align=right>".sprintf("%0.3f",$this->qry_data_source->FieldByName($element->str_column_field))."</td>";
				} else if ($element->str_column_field_type=="boolean") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color'>";
					if ($this->qry_data_source->FieldByName($element->str_column_field)=="Y")
					echo "x";
					echo "</td>";
				} else if ($element->str_column_field_type=="date") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color' align=right>".FormatDate($this->qry_data_source->FieldByName($element->str_column_field))."</td>";
				} else if ($element->str_column_field_type=="custom") {
					echo "<td class='".$str_row_class."' bgcolor='$str_bg_color' >";
					call_user_func($element->fn_callback,$element->str_column_field, $this->qry_data_source);
					echo "</td>";
				}


			}


			echo "</tr>";
			$this->qry_data_source->Next();
		}
		echo "</table>";

	}

	//
	// process parameters from a get or post, usually
	//
	function processParameters($f_arr) {
		$int_parameter=0;

		// load in customer parameters

		reset($this->arr_custom_parameters);

		foreach ($this->arr_custom_parameters as $key => $value) {
			if (!empty($f_arr[$key])) {
				$this->arr_custom_parameters[$key] = $f_arr[$key];
			}
		}
		if (!empty($f_arr['print']))
			$this->b_print=true;

		if (!empty($f_arr['export']))
			$this->b_export = true;

		if (!empty($f_arr['page']))
			$this->setCurrentPage($f_arr['page']);

		if (!empty($f_arr['showdeleted']))
			$this->setShowDeleted($f_arr['showdeleted']=='Y');

		if (!empty($f_arr['pagesize']))
			$this->setPageSize($f_arr['pagesize']);

		if (!empty($f_arr['monthdays']))
			$this->setMonthDays($f_arr['monthdays']);

		while (!empty($f_arr['order'.$int_parameter])) {
			$str_param = $f_arr['order'.$int_parameter];
			$str_direction = '';

			if (strrpos($str_param,'desc') == strlen($str_param)-4) {

				$str_param=substr($str_param, 0, strrpos($str_param,'desc'));
				$str_direction='desc';
			}

			$this->addOrder(trim($str_param), $str_direction);
			$int_parameter++;
		}
		$int_parameter=0;
		while (!empty($f_arr['ff'.$int_parameter])) {
			$str_filter_field = $f_arr['ff'.$int_parameter];
			$str_filter_comparison = $f_arr['fc'.$int_parameter];
			$str_filter_value = $f_arr['fv'.$int_parameter];
			if ($str_filter_value=='-') $str_filter_value='';
			$str_filter_type = $f_arr['ft'.$int_parameter];

			$this->addUniqueFilter($str_filter_field, $str_filter_comparison, $str_filter_value,$str_filter_type);
			$int_parameter++;
		}

	}
}

//
//  ControlButton
//
//  button or special control on the control bar of the grid
//
class ControlButton {
	var $str_control_type;
	var $str_control_onclick;
	var $str_control_image;
	var $str_control_description;
	var $str_control_alignment;

	function ControlButton($f_type, $f_description, $f_image, $f_onclick, $f_alignment) {
		$this->str_control_alignment = $f_alignment;
		$this->str_control_type = $f_type;
		if ($f_type<>'selectioncontrol') {
			if (strpos($f_onclick,'(')==0) {
				$f_onclick .="();";
			}
		}
		$this->str_control_onclick = $f_onclick;
		$this->str_control_image = $f_image;
		$this->str_control_description = $f_description;
	}

	function drawControl($f_parent) {
		$f_grid = $f_parent->grid;
		$str_nav_frame = $f_parent->str_nav_frame;
		$str_content_frame = $f_parent->str_content_frame;
		if ($this->str_control_type=='html') {
			echo "parent.frames['$str_nav_frame'].document.write(\"".$this->str_control_description."\");";
		}
		if ($this->str_control_type=='button') {
			if (!empty($this->str_control_image)) {
				echo "str_click = 'javascript:parent.frames[\"".$f_parent->str_content_frame."\"].".$this->str_control_onclick."';";

				echo "parent.frames['$str_nav_frame'].document.write(\"<a href='\"+str_click+\"'].".$this->str_control_onclick."'><img alt='".$this->str_control_description."' title='".$this->str_control_description."' src='".$this->str_control_image."' border=0 ></a>\");";
			} else {
				echo "str_click = 'javascript:parent.frames[\"".$f_parent->str_content_frame."\"].".$this->str_control_onclick."';";
				echo "parent.frames['$str_nav_frame'].document.write(\"<input type='button' onclick='\"+str_click+\"' value='".$this->str_control_description."'>\");";
			}
		}

		if ($this->str_control_type=='control') {

			if (substr($this->str_control_description,0,6)=='filter') {
				$str_filter_number = substr($this->str_control_description,6,strlen($this->str_control_description));
				echo "parent.frames['$str_nav_frame'].document.write(\"<form name='gridform' method='GET' target='".$f_parent->str_content_frame."' action='".$f_parent->grid->str_submit_url."'>\");";

				echo "parent.frames['$str_nav_frame'].document.write(\"<font class='normaltext'>Filter: <select name='ff$str_filter_number'>\");";

				for ($i=0; $i < count($f_grid->arr_grid_columns); $i++) {
					if ($f_grid->arr_grid_columns[$i]->bool_column_filtered) {
						echo "parent.frames['$str_nav_frame'].document.write(\"<option value='".$f_grid->arr_grid_columns[$i]->str_column_field."' ";
						if (count ($f_grid->arr_grid_filtering)>$str_filter_number) {
							if ($f_grid->arr_grid_filtering[$str_filter_number]->str_filter_field == $f_grid->arr_grid_columns[$i]->str_column_field)
								echo "selected";
						}
						echo ">".$f_grid->arr_grid_columns[$i]->str_column_header."</option>\");";
					}
				}


				echo "parent.frames['$str_nav_frame'].document.write(\"</select><input type=text size=6 name='fv".$str_filter_number."' value='";
				if (count($f_grid->arr_grid_filtering)>$str_filter_number) {
					echo $f_grid->arr_grid_filtering[$str_filter_number]->str_filter_value;
				}
				echo "'><input type='submit' value='Filter'><input type='hidden' name='fc$str_filter_number' value='starts with'><input type='hidden' name='ft$str_filter_number' value='string'>".$f_parent->grid->buildFormString($str_filter_number)."</form>\");";

			} else if (substr($this->str_control_description,0,9)=='advfilter') {
				$str_filter_number = substr($this->str_control_description,9,strlen($this->str_control_description));
				echo "parent.frames['$str_nav_frame'].document.write(\"<form name='gridform' method='GET' target='".$f_parent->str_content_frame."' action='".$f_parent->grid->str_submit_url."'>\");";

				echo "parent.frames['$str_nav_frame'].document.write(\"<font class='normaltext'>Filter: <select name='ff$str_filter_number'>\");";

				for ($i=0; $i < count($f_grid->arr_grid_columns); $i++) {
					if ($f_grid->arr_grid_columns[$i]->bool_column_filtered) {
						echo "parent.frames['$str_nav_frame'].document.write(\"<option value='".$f_grid->arr_grid_columns[$i]->str_column_field."' ";
						if (count ($f_grid->arr_grid_filtering)>$str_filter_number) {
							if ($f_grid->arr_grid_filtering[$str_filter_number]->str_filter_field == $f_grid->arr_grid_columns[$i]->str_column_field)
								echo "selected";
						}
						echo ">".$f_grid->arr_grid_columns[$i]->str_column_header."</option>\");";
					}
				}


				echo "parent.frames['$str_nav_frame'].document.write(\"</select><select name='fc$str_filter_number'><option value='starts with' ";
				if (count ($f_grid->arr_grid_filtering)>$str_filter_number+0)
					if ($f_grid->arr_grid_filtering[$str_filter_number+0]->str_filter_comparison == 'starts with')
						echo 'selected';
				echo ">starts with</option><option value='contains' ";
				if (count ($f_grid->arr_grid_filtering)>$str_filter_number+0)
					if ($f_grid->arr_grid_filtering[$str_filter_number+0]->str_filter_comparison=='contains')
						echo 'selected';

				echo ">contains</option><option value='ends with' ";
				if (count ($f_grid->arr_grid_filtering)>$str_filter_number+0)

					if ($f_grid->arr_grid_filtering[$str_filter_number+0]->str_filter_comparison=='ends with') echo 'selected';

				echo ">ends with</option><option selected value='equals' ";
				if (count ($f_grid->arr_grid_filtering)>$str_filter_number+0)

				if ($f_grid->arr_grid_filtering[$str_filter_number+0]->str_filter_comparison=='equals') echo 'selected';
				echo ">equals</option></select>\");";
				echo "parent.frames['$str_nav_frame'].document.write(\"<input type=text size=6 name='fv".$str_filter_number."' value='";
				if (count($f_grid->arr_grid_filtering)>$str_filter_number) {
					echo $f_grid->arr_grid_filtering[$str_filter_number]->str_filter_value;
				}
				echo "'> <input type='submit' value='Filter'><input type='hidden' name='ft$str_filter_number' value='string'>".$f_parent->grid->buildFormString($str_filter_number)."</form>\");";

			} else if (substr($this->str_control_description,0,3)=='nav') {
				echo "parent.frames['".$f_parent->str_nav_frame."'].document.write(\"<font class='".$f_grid->str_row_class."'>";
				//
				// calculate page size
				//
				$int_num_rows=0;
				if ($f_grid->qry_data_source) {
					  $int_num_rows = $f_grid->qry_data_source->RowCount();
				}

				if ($int_num_rows-($f_grid->int_page_number*$f_grid->int_page_size)<$f_grid->int_page_size) {
					  $int_num_recs = $int_num_rows-($f_grid->int_page_number*$f_grid->int_page_size);
				} else $int_num_recs = $f_grid->int_page_size;

				$int_offset=0;
				if ($int_num_rows>0) $int_offset++;

				echo ("Records ".($f_grid->int_page_number*$f_grid->int_page_size+$int_offset)."-".(($f_grid->int_page_number*$f_grid->int_page_size) + $int_num_recs)." of ".$int_num_rows."&nbsp;&nbsp;&nbsp;");

				if ($f_grid->int_page_number>0) {
					echo "<a target='".$f_parent->str_content_frame."' href='".$f_grid->str_submit_url."?".$f_grid->buildQueryString('page','0')."'><img alt='First' title='First' align=absmiddle src='".$f_grid->str_image_path."resultset_first.png' border=0></a>&nbsp;";
  					echo "<a target='".$f_parent->str_content_frame."' href='".$f_grid->str_submit_url."?".$f_grid->buildQueryString('page',($f_grid->int_page_number-1))."'><img alt='Prev' title='Prev' align=absmiddle src='".$f_grid->str_image_path."resultset_previous.png' border=0></a>&nbsp;&nbsp;";
  				}
  				if (($f_grid->int_page_number*$f_grid->int_page_size+$int_num_recs)<$int_num_rows) {
 				 	echo "<a target='".$f_parent->str_content_frame."' href='".$f_grid->str_submit_url."?".$f_grid->buildQueryString('page',($f_grid->int_page_number+1))."'><img alt='Next' title='Next' align=absmiddle src='".$f_grid->str_image_path."resultset_next.png' border=0></a>  ";
					echo "<a target='".$f_parent->str_content_frame."' href='".$f_grid->str_submit_url."?".$f_grid->buildQueryString('page',floor($int_num_rows/$f_grid->int_page_size))."'><img alt='Last' title='Last' align=absmiddle src='".$f_grid->str_image_path."resultset_last.png' border=0></a>  ";
				  }
				echo "\");";

			} else if (substr($this->str_control_description,0,7)=='refresh') {
				echo "parent.frames['".$f_parent->str_nav_frame."'].document.write(\"";
				echo "&nbsp;&nbsp;<a target='".$f_parent->str_content_frame."' href='".$f_grid->str_submit_url."?".$f_grid->buildQueryString('dummy')."'><img alt='Refresh List' title='Refresh List' align='absmiddle' src='".$f_grid->str_image_path."refresh.gif' border=0></a>&nbsp;&nbsp;";
				echo "\");";

			} else if (substr($this->str_control_description,0,5)=='print') {
				echo "parent.frames['".$f_parent->str_nav_frame."'].document.write(\"";
				echo "&nbsp;&nbsp;<a target='_blank' href='".$f_grid->str_submit_url."?".$f_grid->buildQueryString('dummy')."&print=yes'><img alt='Print View' title='Print View' align='absmiddle' src='".$f_grid->str_image_path."printer.png' border=0></a>&nbsp;&nbsp;";
				echo "\");";

			} else if (substr($this->str_control_description,0,11)=='showdeleted') {
				echo "parent.frames['".$f_parent->str_nav_frame."'].document.write(\"";
				$f_grid->bool_show_deleted=!$f_grid->bool_show_deleted;
				echo "&nbsp;&nbsp;<a target='".$f_parent->str_content_frame."' href='".$f_grid->str_submit_url."?".$f_grid->buildQueryString('dummy')."'>";
				if ($f_grid->bool_show_deleted) {
					echo "<img alt='Show Deleted' title='Show Deleted' align='absmiddle' src='".$f_grid->str_image_path."but_showhidden.gif' border=0>";
				} else {
					echo "<img alt='Hide Deleted' title='Hide Deleted' align='absmiddle' src='".$f_grid->str_image_path."but_showhiddendown.gif' border=0>";

				}
				$f_grid->bool_show_deleted=!$f_grid->bool_show_deleted;

				echo "</a>&nbsp;&nbsp;\");";
			} else if (substr($this->str_control_description,0,8)=='pagesize') {
				echo "parent.frames['$str_nav_frame'].document.write(\"<form name='gridform' method='GET' target='".$f_parent->str_content_frame."' action='".$f_parent->grid->str_submit_url."'>\");";

				echo "parent.frames['$str_nav_frame'].document.write(\"<font class='normaltext'><select onchange='this.form.submit();' name='pagesize'>\");";

				for ($i=20; $i <= 100; $i+=20) {
					echo "parent.frames['$str_nav_frame'].document.write(\"<option value='".$i."' ";
					if ($f_grid->int_page_size==$i)
						echo "selected";
					echo ">".$i." Rows</option>\");";
				}


				echo "parent.frames['$str_nav_frame'].document.write(\"</select>".$f_parent->grid->buildFormString('pagesize')."</form>\");";
			} else if (substr($this->str_control_description,0,9)=='monthdays') {
				echo "parent.frames['$str_nav_frame'].document.write(\"<form name='gridform' method='GET' target='".$f_parent->str_content_frame."' action='".$f_parent->grid->str_submit_url."'>\");";

				echo "parent.frames['$str_nav_frame'].document.write(\"<font class='normaltext'><select onchange='this.form.submit();' name='monthdays'>\");";

				for ($i=1; $i <= 31; $i+=1) {
					echo "parent.frames['$str_nav_frame'].document.write(\"<option value='".$i."' ";
					if ($f_grid->int_month_days==$i)
						echo "selected";
					echo ">".$i."</option>\");";
				}


				echo "parent.frames['$str_nav_frame'].document.write(\"</select>".$f_parent->grid->buildFormString('monthdays')."</form>\");";

			} else if (substr($this->str_control_description,0,4)=='view') {
				echo "parent.frames['$str_nav_frame'].document.write(\"<form name='gridform' method='GET' target='".$f_parent->str_content_frame."' action='".$f_parent->grid->str_submit_url."'>\");";
				$str_qry = "select distinct view_name from grid where grid_name='".$f_parent->grid->str_grid_name."' and view_name<>'all' and user_id=".$_SESSION['int_user_id'];
//				echo $str_qry;
				$qry_view = new Query($str_qry);

				echo "parent.frames['$str_nav_frame'].document.write(\"<font class='normaltext'>View: <select onchange='this.form.submit();' name='view_name'>\");";

				for ($i=0; $i<$qry_view->RowCount();$i++) {
					echo "parent.frames['$str_nav_frame'].document.write(\"<option value='".$qry_view->FieldByName('view_name')."' ";
					if ($f_grid->str_view_name==$qry_view->FieldByName('view_name'))
						echo "selected";
					echo ">".$qry_view->FieldByName('view_name')."</option>\");";
					$qry_view->Next();
				}


				echo "
				function customizegrid() {
					window.open('".$f_parent->grid->str_web_root."include/gridcustomize.php?gridname=".$f_parent->grid->str_grid_name."','new','width=600,height=600,scrollbars=yes,menubar=no,resize=yes');
				}

				parent.frames['$str_nav_frame'].document.write(\"</select> <a href='javascript:parent.$str_content_frame.customizegrid();'><img border=0 src='".$f_parent->grid->str_image_path."but_grid.gif'></a>".$f_parent->grid->buildFormString('view_name')."</form>\");";

			} else if (substr($this->str_control_description,0,6)=='export') {
				echo "parent.frames['".$f_parent->str_nav_frame."'].document.write(\"";
				echo "&nbsp;&nbsp;<a target='_blank' href='".$f_grid->str_submit_url."?&export=yes'><img alt='Export to CSV' title='Export to CSV' align='absmiddle' src='".$f_grid->str_image_path."table_go.png' border=0></a>&nbsp;&nbsp;";
				echo "\");";
			}
		}
		if ($this->str_control_type=='selectioncontrol') {

			$str_filter_number = substr($this->str_control_description,6,strlen($this->str_control_description));

			echo "parent.frames['$str_nav_frame'].document.write(\"<form name='gridform' method='GET' target='".$f_parent->str_content_frame."' action='".$f_parent->grid->str_submit_url."'>\\n\");";

			echo "parent.frames['$str_nav_frame'].document.write(\"<font class='normaltext'> <select name='fv$str_filter_number' onchange='this.form.submit();'>\\n\");";
				echo "parent.frames['$str_nav_frame'].document.write(\"<option value='-' ";
						if (count ($f_grid->arr_grid_filtering)>$str_filter_number) {
							if (($f_grid->arr_grid_filtering[$str_filter_number]->str_filter_value == '-') || ($f_grid->arr_grid_filtering[$str_filter_number]->str_filter_value == ''))
								echo "selected";
						}
						echo ">-Any-</option>\\n\");";

				foreach ($this->str_control_onclick as $key => $value) {
					echo "parent.frames['$str_nav_frame'].document.write(\"<option value='$value' ";
//						if (count ($f_grid->arr_grid_filtering)>$str_filter_number) {
							if (trim(@$f_grid->arr_grid_filtering[$str_filter_number]->str_filter_value) == trim($value))
								echo "selected";
//						}
						echo ">".$key."</option>\\n\");";
				}


				echo "parent.frames['$str_nav_frame'].document.write(\"</select><input type=hidden size=6 name='ff".$str_filter_number."' value='".trim($this->str_control_image)."'> <input type='hidden' name='fc$str_filter_number' value='starts with'><input type='hidden' name='ft$str_filter_number' value='string'>".$f_parent->grid->buildFormString($str_filter_number)."</form>\");";
			
		}
	}
}

class GridForm {
	var $grid;		// points to a Grid class
	var $controls;		// controls at the left

	var $str_nav_frame;
	var $str_content_frame;
	var $str_stylesheet;
	var $str_path_images='../images/';


	// constructor
	function GridForm($f_str_nav_frame='gridmenu',$f_str_content_frame='gridcontent', $f_str_stylesheet='../include/styles.css') {
		$this->str_nav_frame = $f_str_nav_frame;
		$this->str_content_frame = $f_str_content_frame;
		$this->str_stylesheet=$f_str_stylesheet;
	}

	function addButton($str_description, $str_img, $str_onclick, $str_alignment) {
		$this->controls[] = new ControlButton('button',$str_description, $str_img, $str_onclick, $str_alignment);
	}

	function addHTML($str_description, $str_alignment) {
		$this->controls[] = new ControlButton('html',$str_description, '', '', $str_alignment);
	}

	function addControl($str_control, $str_alignment) {
		$this->controls[] = new ControlButton('control',$str_control, '','',$str_alignment);
	}

	function addSelectionControl($str_control, $str_field, $arr_values, $str_alignment) {
		$this->controls[] = new ControlButton('selectioncontrol',$str_control, $str_field, $arr_values,$str_alignment);
	}

	function getControl($str_control) {
	}

	function setGrid($f_grid) {
		$this->grid = $f_grid;
	}

	function setFrames($f_str_nav, $f_str_content) {
		$this->str_nav_frame = $f_str_nav;
		$this->str_content_frame = $f_str_content;
	}


	function drawNavigation() {
		$str_nav_frame=$this->str_nav_frame;
		$str_nav_stylesheet=$this->str_stylesheet;

		echo "
<script language='javascript'>
parent.frames['$str_nav_frame'].document.open();
parent.frames['$str_nav_frame'].document.write(\"<html><head><link href='$str_nav_stylesheet' rel='stylesheet' type='text/css'></head><body marginwidth=0 marginheight=0 topmargin=0 leftmargin=0>\");
parent.frames['$str_nav_frame'].document.write(\"<table width='100%' border=0 cellpadding=3 cellspacing=0>\");
parent.frames['$str_nav_frame'].document.write(\"<TR><TD height=30 valign='top' class='headerText' bgcolor='#e0e0e0'><table border=0 cellpadding=0 cellspacing=0><tr>\");";
		//
		// draw left controls
		//
		for ($i=0;$i < count($this->controls); $i++) {
			if ($this->controls[$i]->str_control_alignment=='left') {
				echo "parent.frames['$str_nav_frame'].document.write(\"<td valign=top>\");";
				$this->controls[$i]->drawControl($this);
				echo "parent.frames['$str_nav_frame'].document.write(\"</td>\");";
			}
		}

		echo "parent.frames['$str_nav_frame'].document.write(\"</tr></table></td>\");
parent.frames['$str_nav_frame'].document.write(\"<td height=30 valign='top' class='normalText' bgcolor='#e0e0e0'><table border=0 cellpadding=0 cellspacing=0><tr>\");";
		//
		// draw center controls
		//
		for ($i=0;$i < count($this->controls); $i++) {
			if ($this->controls[$i]->str_control_alignment=='center') {
				echo "parent.frames['$str_nav_frame'].document.write(\"<td valign=top>\");";

				$this->controls[$i]->drawControl($this);
				echo "parent.frames['$str_nav_frame'].document.write(\"</td>\");";
			}
		}

		echo "parent.frames['$str_nav_frame'].document.write(\"</tr></table></td>\");
parent.frames['$str_nav_frame'].document.write(\"<td  height=30 valign='top' class='normalText' bgcolor='#e0e0e0' align=right><table border=0 cellpadding=0 cellspacing=0><tr>\");";
		//
		// draw right controls
		//
		for ($i=0;$i < count($this->controls); $i++) {
			if ($this->controls[$i]->str_control_alignment=='right') {
				echo "parent.frames['$str_nav_frame'].document.write(\"<td valign=top>\");";

				$this->controls[$i]->drawControl($this);
				echo "parent.frames['$str_nav_frame'].document.write(\"</td>\");";

			}
		}
		echo "parent.frames['$str_nav_frame'].document.write(\"</tr></table></TD></TR></table></body></html>\");
parent.frames['$str_nav_frame'].document.close();
</script>";
	}

	function draw() {
		$this->grid->prepareQuery();

		if ($this->grid->b_print == true) {
			$this->grid->drawPrintGrid();
		} 
		else if ($this->grid->b_export == true) {
			$this->grid->exportGrid();
		}
		else {
			$this->drawNavigation();
			$this->grid->drawGrid();
		}

//==========
// this is the way it was before Luk added the export functionality
// March 2007
//==========
/*
		if ($this->grid->b_print==false) {
			$this->drawNavigation();
			$this->grid->drawGrid();
		} else {
			$this->grid->drawPrintGrid();
		}
*/
	}
}


//
//  DBGrid
//
//  manages a grid and loads it from a table
//
class DBGrid extends Grid {
	var $str_grid_name;
	var $str_view_name='default';
	function DBGrid($f_grid) {
		$this->str_grid_name = $f_grid;
		parent::Grid();
	}
	function loadView($f_view='default') {
		$this->str_view_name=$f_view;
		$qry = new Query("select * from grid where user_id=".$_SESSION['int_user_id']." and grid_name='".$this->str_grid_name."' and view_name='".$this->str_view_name."' and visible='Y' order by column_order");

		if ($qry->RowCount()>0) {
			unset($this->arr_grid_columns);
			for ($i=0;$i<$qry->RowCount();$i++) {
				$b_is_filtered = $qry->FieldByName('can_filter')=='Y';
				$int_col_width=$qry->FieldByName('width');
				$str_callback=$qry->FieldByName('callback');
				$this->addColumn($qry->FieldByName('column_name'),$qry->FieldByName('field_name'),
					$qry->FieldByName('field_type'),$b_is_filtered,$int_col_width,$str_callback);
				$qry->Next();
			}
		} else {
			$qry_all = new Query("select * from grid where user_id=".$_SESSION['int_user_id']." and grid_name='".$this->str_grid_name."' and view_name='all' order by column_order");
			if ($qry->RowCount()==0) {
				for ($i=0;$i<count($this->arr_grid_columns);$i++) {
					$str_insert = "INSERT INTO grid (user_id, 
						grid_name, 
						view_name, 
						column_name,
						field_name,
						field_type,
						width,
						can_filter,
						callback,
						visible,
						column_order)
						VALUES (
							".$_SESSION['int_user_id'].",
							\"".$this->str_grid_name."\",
							\"all\",
							\"".addslashes($this->arr_grid_columns[$i]->str_column_header)."\",
							\"".addslashes($this->arr_grid_columns[$i]->str_column_field)."\",
							\"".addslashes($this->arr_grid_columns[$i]->str_column_field_type)."\",
							\"".addslashes($this->arr_grid_columns[$i]->str_column_width)."\",
							\"".($this->arr_grid_columns[$i]->bool_column_filtered?"Y":"N")."\",
							\"".addslashes($this->arr_grid_columns[$i]->fn_callback)."\",
							\"Y\",
							\"".$i."\"
						)
						";
					$qry_all->Query($str_insert);

					$str_insert = "INSERT INTO grid (user_id, 
						grid_name, 
						view_name, 
						column_name,
						field_name,
						field_type,
						width,
						can_filter,
						callback,
						visible,
						column_order)
						VALUES (
							".$_SESSION['int_user_id'].",
							\"".$this->str_grid_name."\",
							\"default\",
							\"".addslashes($this->arr_grid_columns[$i]->str_column_header)."\",
							\"".addslashes($this->arr_grid_columns[$i]->str_column_field)."\",
							\"".addslashes($this->arr_grid_columns[$i]->str_column_field_type)."\",
							\"".addslashes($this->arr_grid_columns[$i]->str_column_width)."\",
							\"".($this->arr_grid_columns[$i]->bool_column_filtered?"Y":"N")."\",
							\"".addslashes($this->arr_grid_columns[$i]->fn_callback)."\",
							\"Y\",
							\"".$i."\"
						)
						";
//					echo $str_insert;
					$qry_all->Query($str_insert);


					
				}
			}
			
		}
	}
	function buildQueryString($f_str_style='order',$f_str_field='') {
		$str_return = parent::buildQueryString($f_str_style,$f_str_field);
		if ($f_str_field<>'view_name') {
			$str_return .= "&view_name=".$this->str_view_name;
		}
		return $str_return;
	}

	function buildFormString($f_str_field) {
		$str_return = parent::buildFormString($f_str_field);
		if ($f_str_field<>'view_name') {
			$str_return .= "<input type='hidden' name='view_name' value='".$this->str_view_name."'>";
		}
		return $str_return;
	}
	function processParameters($f_arr) {
		if (!empty($f_arr['view_name'])) {
			$this->str_view_name = $f_arr['view_name'];
			$this->loadView($this->str_view_name);

		}
		parent::processParameters($f_arr);

	}

}


?>