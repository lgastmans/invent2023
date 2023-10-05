<script language="javascript">

	function RoundUp(flt_number) {
		if (flt_number < 0) {
			flt_number = flt_number * (-1);
			is_negative = true;
		} else
			is_negative = false;

		int_part = Math.round((flt_number * 100));

		to_round = int_part % 10;

		if ((to_round == 0) || (to_round == 5))
			flt_retval = (int_part / 100);
		else
		if (to_round < 5)
			flt_retval = (int_part + (5 - to_round))/100;
		else
			flt_retval = (int_part + (10 - to_round))/100;

		if (is_negative)
			flt_retval = flt_retval * (-1);

		return flt_retval;
	}

</script>