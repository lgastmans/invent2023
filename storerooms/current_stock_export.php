<?
    header("Content-Type: application/text; name=storeroom_current_stock.txt");
    header("Content-Transfer-Encoding: binary");
    header("Content-Disposition: attachment; filename=storeroom_current_stock.txt");
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
?>