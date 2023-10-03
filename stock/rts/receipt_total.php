<?
    require_once("../../include/const.inc.php");
    require_once("../../include/session.inc.php");
    
    if (IsSet($_GET['discount']))
        $int_discount = $_GET['discount'];
    else
        $int_discount = 0;
        
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />

        <script language="javascript">
          function setDiscount(evt) {
                        evt = (evt) ? evt : event;
                        var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
        
                        oTextBoxDiscount = document.receipt_total.discount;
        
                        if (charCode == 13 || charCode == 3 || charCode == 9) {
                                parent.frames["frame_list"].document.location = "receipt_list.php?action=set_discount&discount="+oTextBoxDiscount.value;
                        }
                        return true;
          }
        </script>

</head>

<body id='body_bgcolor' leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

	<form name='receipt_total' method='GET'>

<table width='100%' border='0' cellpadding='0' cellspacing='0'>
<tr><td align='center'>
<?
	boundingBoxStart("750", "../../images/blank.gif");
?>	
	<table width='60%' height='100%' border='0' cellpadding=0 cellspacing=0>
		<tr>
			<td width='65%' class="normaltext_bold" align='right'>Total :&nbsp;</td>
			<td align='right'>
				<span id="receipt_total" class="normaltext_bold">
					<?
						echo number_format($_GET['total'],2);
					?>
				</span>
			</td>
		</tr>
		<tr>
			<td width='10%' class="normaltext_bold" align='right' id='receipt_discount_label'>
			    Discount:&nbsp;
			</td>
			<td align='right'>
				<span id="receipt_discount" class="normaltext_bold">
				<input type="text" name="discount" value="<?echo $int_discount?>" class="input_50" onkeypress="setDiscount(event)">&nbsp;<font class='<?echo $str_class_total?>'>%</font>
				</span>
			</td>
		</tr>
		<tr>
			<td width='10%' class="normaltext_bold" align='right' id='receipt_grand_total_label'>
			    <? if ($int_discount > 0) echo "Grand Total :&nbsp;"; ?>
			</td>
			<td align='right'>
				<span id="receipt_grand_total" class="normaltext_bold">
				<?
                                    $grand_total = 0;
                                    if ($int_discount > 0) {
                                        $grand_total = RoundUp($_GET['total'] * (1 - $int_discount/100));
					echo number_format($grand_total, 2, '.', ',');
				    }
				?>
				</span>
			</td>
		</tr>
	</table>
	
<?
    boundingBoxEnd("750", "../../images/blank.gif");
?>
</td></tr>
</table>

	</form>
</body>
</html>