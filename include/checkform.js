function trim(s) {
return s.replace(/^\s+|\s+$/g, "");
}
function isFloat(s)
{
var n = trim(s);
return n.length>0 && !(/[^0-9.]/).test(n) && (/\.\d/).test(n);
}
function isInteger(s)
{
var n = trim(s);
return n.length > 0 && !(/[^0-9]/).test(n);

}

/*
function isEmail1 (s){
 return (s.indexOf("@")<0) || (s.indexOf(".")<0) || (s.indexOf("@") >= s.value.lastIndexOf(".")) || (s.length-s.lastIndexOf(".")<3));
}
*/

function isDate1(dtStr,dtCh,minYear,maxYear){
	var daysInMonth = DaysArray(12);
	var pos1=dtStr.indexOf(dtCh);
	var pos2=dtStr.indexOf(dtCh,pos1+1);
	var strMonth=dtStr.substring(0,pos1);
	var strDay=dtStr.substring(pos1+1,pos2);
	var strYear=dtStr.substring(pos2+1);
	strYr=strYear;
	if (strDay.charAt(0)=="0" && strDay.length>1) strDay=strDay.substring(1)
	if (strMonth.charAt(0)=="0" && strMonth.length>1) strMonth=strMonth.substring(1);
	for (var i = 1; i <= 3; i++) {
		if (strYr.charAt(0)=="0" && strYr.length>1) strYr=strYr.substring(1);
	}
	month=parseInt(strMonth);
	day=parseInt(strDay);
	year=parseInt(strYr);
	if (pos1==-1 || pos2==-1){
		alert("The date format should be : mm/dd/yyyy");
		return false
	}
	if (strMonth.length<1 || month<1 || month>12){
		alert(month+"Please enter a valid month");
		return false
	}
	if (strDay.length<1 || day<1 || day>31 || (month==2 && day>daysInFebruary(year)) || day > daysInMonth[month]){
		alert("Please enter a valid day");
		return false
	}
	if (strYear.length != 4 || year==0 || year<minYear || year>maxYear){
		alert("Please enter a valid 4 digit year between "+minYear+" and "+maxYear);
		return false
	}
	if (dtStr.indexOf(dtCh,pos2+1)!=-1 || isInteger(stripCharsInBag(dtStr, dtCh))==false){
		alert("Please enter a valid date");
		return false
	}
return true
}

function stripCharsInBag(s, bag){
	var i;
    var returnString = "";
    // Search through string's characters one by one.
    // If character is not in bag, append to returnString.
    for (i = 0; i < s.length; i++){   
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1) returnString += c;
    }
    return returnString;
}

function IsTime(strTime) {
    //var strTime1 = /^(\d{1,2})(\:)(\d{2})\2(\d{2})(\ )\w[AM|PM|am|pm]$/;
    var strTime2 = /^(\d{1,2})(\:)(\d{2})(\ )\w[A|P|a|p]\w[M|m]$/;
    var strTime3 = /^(\d{1,2})(\:)(\d{1,2})$/;
   // var strFormat1 = strTime.match(strTime1);
    var strFormat2 = strTime.match(strTime2);
    var strFormat3 = strTime.match(strTime3);
// Check to see if it matches one of the
//     3 Format Strings.
    if (strFormat2 == null && strFormat3 == null) {
            return false;
    }
    /*else if (strFormat1 != null) {
            // Validate for this format: 3:48:01 PM
                    if (strFormat1[1] > 12 || strFormat1[1] < 00) {
                            return false;
                    }
                    if (strFormat1[3] > 59 || strFormat1[3] < 00) {
                            return false;	
                    }
                            if (strFormat1[4] > 59 || strFormat1[4] < 00) {
                                    return false;	
                            }
                    }
*/
    else if (strFormat2 != null) {
            // Validate for this format: 3:48 PM
        if (strFormat2[1] > 12 || strFormat2[1] < 00) {
                return false;
        }
        if (strFormat2[3] > 59 || strFormat2[3] < 00) {
                return false;	
        }
    }
    else if (strFormat3 != null) {
            // Validate for this format: 15:48
            if (strFormat3[1] > 23 || strFormat3[1] < 00) {
                    return false;
            }
                    if (strFormat3[3] > 59 || strFormat3[3] < 00) {
                            return false;	
                    }
    }
  return true;
}
function isEmpty(str){
  return (str == null) || (str.length == 0);
}
// returns true if the string is a valid email
function isEmail(str){
  if(isEmpty(str)) return false;
  var re = /^[^\s()<>@,;:\/]+@\w[\w\.-]+\.[a-z]{2,}$/i
  return re.test(str);
}
// returns true if the string only contains characters A-Z or a-z
function isAlpha(str){
  var re = /[^a-zA-Z]/g
  if (re.test(str)) return false;
  return true;
}
// returns true if the string only contains characters 0-9
function isNumeric(str){
  var re = /[\D]/g
  if (re.test(str)) return false;
  return true;
}
// returns true if the string only contains characters A-Z, a-z or 0-9
function isAlphaNumeric(str){
  var re = /[^a-zA-Z0-9]/g
  if (re.test(str)) return false;
  return true;
}
// returns true if the string's length equals "len"
function isLength(str, len){
  return str.length == len;
}
// returns true if the string's length is between "min" and "max"
function isLengthBetween(str, min, max){
  return (str.length >= min)&&(str.length <= max);
}
// returns true if the string is a US phone number formatted as...
// (000)000-0000, (000) 000-0000, 000-000-0000, 000.000.0000, 000 000 0000, 0000000000
function isPhoneNumber(str){
	
	
  var re = /^\(?[2-9]\d{2}[\)\.-]?\s?\d{3}[\s\.-]?\d{4}$/
  return re.test(str);
}
// returns true if the string is a valid date formatted as...
// mm dd yyyy, mm/dd/yyyy, mm.dd.yyyy, mm-dd-yyyy
function isDate(str){
  var re = /^(\d{1,2})[\s\.\/-](\d{1,2})[\s\.\/-](\d{4})$/
  if (!re.test(str)) return false;
  var result = str.match(re);
  var m = parseInt(result[1],10);
  var d = parseInt(result[2],10);
  var y = parseInt(result[3],10);
  if(m < 1 || m > 12 || y < 1900 || y > 2100) return false;
  if(m == 2){
          var days = ((y % 4) == 0) ? 29 : 28;
  }else if(m == 4 || m == 6 || m == 9 || m == 11){
          var days = 30;
  }else{
          var days = 31;
  }
  return (d >= 1 && d <= days);
}
// returns true if "str1" is the same as the "str2"
function isMatch(str1, str2){
  return str1 == str2;
}
// returns true if the string contains only whitespace
// cannot check a password type input for whitespace
function isWhitespace(str){ // NOT USED IN FORM VALIDATION
  var re = /[\S]/g
  if (re.test(str)) return false;
  return true;
}
// removes any whitespace from the string and returns the result
// the value of "replacement" will be used to replace the whitespace (optional)
function stripWhitespace(str, replacement){// NOT USED IN FORM VALIDATION
  if (replacement == null) replacement = '';
  var result = str;
  var re = /\s/g
  if(str.search(re) != -1){
    result = str.replace(re, replacement);
  }
  return result;
}

function isZipCode(value) {
   var RegExp = /^\d{5}([\-]\d{4})?$/;
   return (RegExp.test(value));
}


