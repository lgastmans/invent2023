<?

function trunc($val, $f="0")
{
    if(($p = strpos($val, '.')) !== false) {
        $val = floatval(substr($val, 0, $p + 1 + $f));
    }
    return $val;
}

function ExpandAmountRs($anAmount) {
    
    $positions = array(
        0=>'',
        1=>'thousand',
        2=>'million',
        3=>'billion');
    $hundreds = array(
        0=>'',
        1=>'one hundred ',
        2=>'two hundred ',
        3=>'three hundred ',
        4=>'four hundred ',
        5=>'five hundred ',
        6=>'six hundred ',
        7=>'seven hundred ',
        8=>'eight hundred ',
        9=>'nine hundred ');
    $specialTens = array(
        0=>'ten ',
        1=>'eleven ',
        2=>'twelve ',
        3=>'thirteen ',
        4=>'fourteen ',
        5=>'fifteen ',
        6=>'sixteen ',
        7=>'seventeen ',
        8=>'eighteen ',
        9=>'nineteen ');
    $tens = array(
        2=>'twenty ',
        3=>'thirty ',
        4=>'forty ',
        5=>'fifty ',
        6=>'sixty ',
        7=>'seventy ',
        8=>'eighty ',
        9=>'ninety ');
    $ones = array(
        0=>'',
        1=>'one ',
        2=>'two ',
        3=>'three ',
        4=>'four ',
        5=>'five ',
        6=>'six ',
        7=>'seven ',
        8=>'eight ',
        9=>'nine ');


    $numberOfIterations = (strlen((int)($anAmount)) + 2) / 3;
    $outputString = '';
    $convertedString = sprintf('%.' + ($numberOfIterations * 3) + 'f',(int)($anAmount));
    
    for ($I=0; $I<$numberOfIterations; $I++) {
        $int_val = ($I - 1) * 3 + 1;
        if (substr($convertedString, $int_val, 3) <> '000') {
            $outputString = $outputString.$hundreds[$convertedString[$int_val]];
            switch ($convertedString[($I - 1) * 3 + 2]) {
              case 0:
                  $outputString = $outputString.$ones[$convertedString[($I - 1) * 3 + 3]];
                  break;
              case 1:
                  $outputString = $outputString.$specialTens[$convertedString[($I - 1) * 3 + 3]];
                  break;
              case 2:
              case 3:
              case 4:
              case 5:
              case 6:
              case 7:
              case 8:
              case 9:
                  $outputString = $outputString + $tens[$convertedString[($I - 1) * 3 + 2]] +
                        $ones[$convertedString[($I - 1) * 3 + 3]];
                  break;
            }        
            $outputString = $outputString + $positions[$numberOfIterations - $I];
        }
    }

    if (strlen($outputString) > 0) 
        $outputString = 'Rupees '.$outputString;
  
    $convertedString = sprintf('%.2d', (trunc(($anAmount - trunc($anAmount)) * 100)));

    if ($convertedString <> '00') {
        $outputString = $outputString.'Paise ';
        switch ($convertedString[1]) {
            case 0:
                $outputString = $outputString + $ones[$convertedString[2]];
                break;
            case 1:
                $outputString = $outputString + $specialTens[$convertedString[2]];
                break;
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
                $outputString = $outputString + $tens[$convertedString[1]] + $ones[$convertedString[2]];
                break;
        }
    }
  
    return $outputString.'ONLY';
}
?>