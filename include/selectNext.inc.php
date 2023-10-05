<script language="javascript">
function selectNext(aForm, elem, event) {

	if (event.keyCode == '13') {

  	// array that will hold the text fields of the form
  	var arrFields = new Array();
  	var arrPos = new Array();
  	
  	// get all the elements of the form
  	intCounter = 0;
  	for (var i = 0; i < aForm.elements.length; i++)	{
  
  			var j = aForm.elements[i];
  
  			switch (j.type) {
  				case 'text': {
  					arrFields[intCounter] = j.name;
  					arrPos[intCounter] = i;
  					intCounter++;
  				}
  			}
  	}
  	
  	intCurElemPos = -1;
  	// locate the current element "elem"
  	for (i = 0; i < arrFields.length; i++)
  	{
  		if (arrFields[i] == elem.name) {
  			intCurElemPos = arrPos[i];
  			break;
  		}
  	}
  	
		// loop to first element in case "elem" is currently the last element
		if (intCurElemPos == arrFields.length-1)
			intCurElemPos = arrPos[0];
		else
			intCurElemPos = arrPos[i+1];
		
  	
  	// select the element listed after "elem"
		aForm.elements[intCurElemPos].select();
		aForm.elements[intCurElemPos].focus();
		
	}
}

</script>
