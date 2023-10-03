<script language="javascript">

function removeCommas(aNum) {

	//remove any commas
	aNum=aNum.replace(/,/g,"");

	//remove any spaces
	aNum=aNum.replace(/\s/g,"");

	return aNum;

}//end of removeCommas(aNum)


//this checks whether the number entered does not have several decimals
//or non-numeric characters
function checkNum(aNum) {
	var isOK=0;
	var aNum=aNum+"";
	
	//if the number has one or none decimal points, lastIndexOf and indexOf
	//will give the same answer
	if (aNum.lastIndexOf(".")!=aNum.indexOf("."))
		isOK=1;
	else
		//here a regular expression is used to check for numbers and decimals
	if (aNum.match(/[^0-9.]/))
		isOK=2;
		
	return isOK;
}

</script>