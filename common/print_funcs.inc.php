<?
//==============================================================================
// This class requires the db.inc.php file to be included
//------------------------------------------------------------------------------

class print_page {
  
    var $int_page_width = 80;
    var $int_total_lines = 65;
    var $int_total_columns = 2;
    var $query;
    var $arr_columns = array(); // 0 - field; 1 - title; 2 - length; 3 - align; 4 - type; 5 - function for 'custom' type
    var $int_space_between = 1;
    var $int_linecounter_start = 0;
    var $int_page_from = 1;
    var $int_page_to = 1;
    var $str_print_all = 'Y';
    
    function get_header() {
        $str_double = '';
        $str_single = '';
        for ($i=0; $i<$this->int_page_width; $i++) {
            $str_double .= '=';
            $str_single .= '-';
        }
        
        $str_titles = '';
        for ($j=0; $j < count($this->arr_columns); $j++) {
            if ($this->arr_columns[$j][3] == 'left') {
                if ($this->arr_columns[$j][4] == 'string')
                    $str_titles .= PadWithCharacter($this->arr_columns[$j][1], ' ', $this->arr_columns[$j][2])." ";
                else if ($this->arr_columns[$j][4] == 'number')
                    $str_data .= PadWithCharacter($this->arr_columns[$j][1], ' ', $this->arr_columns[$j][2])." ";
                else if ($this->arr_columns[$j][4] == 'custom')
                    $str_titles .= PadWithCharacter($this->arr_columns[$j][1], ' ', $this->arr_columns[$j][2])." ";
                else if ($this->arr_columns[$j][4] == 'dotted')
                    $str_titles .= PadWithCharacter($this->arr_columns[$j][1], ' ', $this->arr_columns[$j][2])." ";
            }
            else {
                if ($this->arr_columns[$j][4] == 'string')
                    $str_titles .= StuffWithCharacter($this->arr_columns[$j][1], ' ', $this->arr_columns[$j][2])." ";
                else if ($this->arr_columns[$j][4] == 'number')
                    $str_titles .= StuffWithCharacter($this->arr_columns[$j][1], ' ', $this->arr_columns[$j][2])." ";
                else if ($this->arr_columns[$j][4] == 'custom')
                    $str_titles .= StuffWithCharacter($this->arr_columns[$j][1], ' ', $this->arr_columns[$j][2])." ";
                else if ($this->arr_columns[$j][4] == 'dotted')
                    $str_titles .= StuffWithCharacter($this->arr_columns[$j][1], ' ', $this->arr_columns[$j][2])." ";
            }
        }
        $str_titles = substr($str_titles, 0, strlen($str_titles)-1);
        
        $str_space_between = '';
        for ($i=0; $i<$this->int_space_between; $i++)
            $str_space_between .= " ";
        
        $str_title = $str_titles;
        for ($i=0; $i<$this->int_total_columns -1; $i++) {
            $str_title .= $str_space_between.$str_titles;
        }
           
        $str_header = $str_double."\n".$str_title."\n".$str_single."\n";
        
        return $str_header;
    }
    
    function get_arr_pos($page_count, $line_counter) {
        $int_retval = 0;
        $int_retval = ($page_count-1) * $this->int_total_lines;
        $int_retval = $int_retval + $line_counter - $this->int_linecounter_start;

        return $int_retval;
    }

    function get_data() {
        $int_line_counter = $this->int_linecounter_start;
        $int_col_count = 0;
        $int_page_count = 1;
        
        $arr_data = array();
        
        for ($i=0; $i<$this->query->RowCount(); $i++) {
                
                $str_data = '';
                for ($j=0; $j < count($this->arr_columns); $j++) {
                    if ($this->arr_columns[$j][3] == 'left') {
                        if ($this->arr_columns[$j][4] == 'string')
                            $str_data .= PadWithCharacter($this->query->FieldByName($this->arr_columns[$j][0]), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'number')
                            $str_data .= PadWithCharacter(number_format($this->query->FieldByName($this->arr_columns[$j][0]), 2, '.', ''), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'custom')
                            $str_data .= PadWithCharacter(call_user_func($this->arr_columns[$j][5], $this->query), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'dotted')
                            $str_data .= PadWithCharacter('', '.', $this->arr_columns[$j][2])." ";
                    }
                    else {
                        if ($this->arr_columns[$j][4] == 'string')
                            $str_data .= StuffWithCharacter($this->query->FieldByName($this->arr_columns[$j][0]), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'number')
                            $str_data .= StuffWithCharacter(number_format($this->query->FieldByName($this->arr_columns[$j][0]), 2, '.', ''), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'custom')
                            $str_data .= StuffWithCharacter(call_user_func($this->arr_columns[$j][5], $this->query), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'dotted')
                            $str_data .= StuffWithCharacter('', '.', $this->arr_columns[$j][2])." ";
                    }
                }
                $str_data = substr($str_data, 0, strlen($str_data)-1);
                
                if ($this->str_print_all == 'Y') {
                    $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                    $arr_data[$int_arr_pos][$int_col_count] = $str_data;
                }
                else if (($int_page_count >= $this->int_page_from) && ($int_page_count <= $this->int_page_to)) {
                    $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                    $arr_data[$int_arr_pos][$int_col_count] = $str_data;
                }
                
                $int_line_counter++;
                if ($int_page_count == 1) {
                    if ($int_line_counter == ($this->int_total_lines -1)) {
                        if ($int_col_count < $this->int_total_columns-1){
                            $int_col_count++;
                            $int_line_counter = $this->int_linecounter_start;
                        }
                        else {
                            if ($this->str_print_all == 'Y') {
                                $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                                $arr_data[$int_arr_pos][0] = "page ".$int_page_count."%e -";
                            }
                            else if (($int_page_count >= $this->int_page_from) && ($int_page_count <= $this->int_page_to)) {
                                $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                                $arr_data[$int_arr_pos][0] = "page ".$int_page_count."%e -";
                            }
                            $int_col_count = 0;
                            $int_page_count++;
                            $int_line_counter = 0;
                        }
                    }
                }
                else {
                    if ($int_line_counter == ($this->int_total_lines -1)) {
                        if ($int_col_count < $this->int_total_columns-1){
                                $int_col_count++;
                        }
                        else {
                            if ($this->str_print_all == 'Y') {
        			$int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                                $arr_data[$int_arr_pos][0] = "page ".$int_page_count."   date ".date('d-m-Y', time())."%e -";
                            }
                            else if (($int_page_count >= $this->int_page_from) && ($int_page_count <= $this->int_page_to)) {
        			$int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                                $arr_data[$int_arr_pos][0] = "page ".$int_page_count."   date ".date('d-m-Y', time())."%e -";
                            }
                            $int_col_count = 0;
                            $int_page_count++;
                        }
                        $int_line_counter = 0;
                    }
                }
                $this->query->Next();
        }
        
        $str_space_between = '';
        for ($i=0; $i<$this->int_space_between; $i++)
            $str_space_between .= " ";

        $str_data = '';
        foreach ($arr_data as $value) {
            for ($j=0; $j<$this->int_total_columns; $j++) {
                $str_cell = " ";
                if (IsSet($value[$j]))
                        $str_cell = $value[$j];
                $str_data .= $str_cell.$str_space_between;
            }
            $str_data = substr($str_data, 0, strlen($str_data)-1-$this->int_space_between);
            $str_data .= "\n";
        }
       
        return $str_data."%e";
    }
    
    function get_data_sorted($str_sort_field) {
        $int_line_counter = $this->int_linecounter_start;
        $int_col_count = 0;
        $int_page_count = 1;
        
        $arr_data = array();
        
        $str_current_value = '';
        
        for ($i=0; $i<$this->query->RowCount(); $i++) {
            
                if ($str_current_value <> $this->query->FieldByName($str_sort_field)) {
                    
                    if ($int_line_counter + 4 >= $this->int_total_lines) {
                        if ($this->str_print_all == 'Y') {
                            $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                            $arr_data[$int_arr_pos][0] = "page ".$int_page_count."   date ".date('d-m-Y', time())."%e -";
                        }
                        else if (($int_page_count >= $this->int_page_from) && ($int_page_count <= $this->int_page_to)) {
                            $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                            $arr_data[$int_arr_pos][0] = "page ".$int_page_count."   date ".date('d-m-Y', time())."%e -";
                        }
                        $int_col_count = 0;
                        $int_line_counter = 0;
                        $int_page_count++;
                    }
                    
                    if ($this->str_print_all == 'Y') {
                        $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                        $arr_data[$int_arr_pos][$int_col_count] = ' ';
                    }
                    else if (($int_page_count >= $this->int_page_from) && ($int_page_count <= $this->int_page_to)) {
                        $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                        $arr_data[$int_arr_pos][$int_col_count] = ' ';
                    }
                    $int_line_counter++;
                    
                    if ($this->str_print_all == 'Y') {
                        $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                        $arr_data[$int_arr_pos][$int_col_count] = $this->query->FieldByName($str_sort_field);
                    }
                    else if (($int_page_count >= $this->int_page_from) && ($int_page_count <= $this->int_page_to)) {
                        $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                        $arr_data[$int_arr_pos][$int_col_count] = $this->query->FieldByName($str_sort_field);
                    }
                    $int_line_counter++;
                    
                    if ($this->str_print_all == 'Y') {
                        $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                        $arr_data[$int_arr_pos][$int_col_count] = PadWithCharacter('', '-', strlen($this->query->FieldByName($str_sort_field)));
                    }
                    else if (($int_page_count >= $this->int_page_from) && ($int_page_count <= $this->int_page_to)) {
                        $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                        $arr_data[$int_arr_pos][$int_col_count] = PadWithCharacter('', '-', strlen($this->query->FieldByName($str_sort_field)));
                    }
                    $int_line_counter++;
                    
                    $str_current_value = $this->query->FieldByName($str_sort_field);
                }
                
                $str_data = '';
                for ($j=0; $j < count($this->arr_columns); $j++) {
                    if ($this->arr_columns[$j][3] == 'left') {
                        if ($this->arr_columns[$j][4] == 'string')
                            $str_data .= PadWithCharacter($this->query->FieldByName($this->arr_columns[$j][0]), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'number')
                            $str_data .= PadWithCharacter(number_format($this->query->FieldByName($this->arr_columns[$j][0]), 2, '.', ''), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'custom')
                            $str_data .= PadWithCharacter(call_user_func($this->arr_columns[$j][5], $this->query), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'dotted')
                            $str_data .= PadWithCharacter('', '.', $this->arr_columns[$j][2])." ";
                    }
                    else {
                        if ($this->arr_columns[$j][4] == 'string')
                            $str_data .= StuffWithCharacter($this->query->FieldByName($this->arr_columns[$j][0]), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'number')
                            $str_data .= StuffWithCharacter(number_format($this->query->FieldByName($this->arr_columns[$j][0]), 2, '.', ''), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'custom')
                            $str_data .= StuffWithCharacter(call_user_func($this->arr_columns[$j][5], $this->query), ' ', $this->arr_columns[$j][2])." ";
                        else if ($this->arr_columns[$j][4] == 'dotted')
                            $str_data .= StuffWithCharacter('', '.', $this->arr_columns[$j][2])." ";
                    }
                }
                $str_data = substr($str_data, 0, strlen($str_data)-1);
                
                if ($this->str_print_all == 'Y') {
                    $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                    $arr_data[$int_arr_pos][$int_col_count] = $str_data;
                }
                else if (($int_page_count >= $this->int_page_from) && ($int_page_count <= $this->int_page_to)) {
                    $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                    $arr_data[$int_arr_pos][$int_col_count] = $str_data;
                }
                
                $int_line_counter++;
                
                if ($int_line_counter >= ($this->int_total_lines -1)) {
                        if ($int_col_count < $this->int_total_columns-1)
                                $int_col_count++;
                        else {
                            if ($this->str_print_all == 'Y') {
                                $int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                                $arr_data[$int_arr_pos][0] = "page ".$int_page_count."   date ".date('d-m-Y', time())."%e -";
                            }
                            else if (($int_page_count >= $this->int_page_from) && ($int_page_count <= $this->int_page_to)) {
				$int_arr_pos = $this->get_arr_pos($int_page_count, $int_line_counter);
                                $arr_data[$int_arr_pos][0] = "page ".$int_page_count."   date ".date('d-m-Y', time())."%e -";
                            }
                            $int_col_count = 0;
                            $int_line_counter = 0;
                            $int_page_count++;
                        }
                        $int_line_counter = 0;
                }
            
            $this->query->Next();
        }
        
        $str_space_between = '';
        for ($i=0; $i<$this->int_space_between; $i++)
            $str_space_between .= " ";
        
        $str_data = '';
        foreach ($arr_data as $value) {
            for ($j=0; $j<$this->int_total_columns; $j++) {
                $str_cell = '';
                if (IsSet($value[$j]))
                        $str_cell = $value[$j];
                $str_data .= $str_cell.$str_space_between;
            }
            $str_data = substr($str_data, 0, strlen($str_data)-$this->int_space_between);
            $str_data .= "\n";
        }
        
        return $str_data."%e";
    }    
    
}
?>
