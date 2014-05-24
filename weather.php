<?php

define("WEATHERURI","http://www.cwb.gov.tw/V7/forecast/town368/7Day/6300800.htm");

$toParse=file_get_contents(WEATHERURI);
$toParse=str_replace("<font color=#CC3366>","",$toParse);
$toParse=str_replace("</font>","",$toParse);
$toParse=str_replace(" ","",$toParse);
$toParse=str_replace(" style=\"min-width:72px;\"","",$toParse);
$toParse=str_replace("</tr>","",$toParse);
$toParse=str_replace("</td>","",$toParse);
$toParse=str_replace("","",$toParse);
$toParse=explode('<tdstyle="min-width:72px;"',$toParse);
foreach($toParse as &$value){
  $value = explode("\n", $value);
}
print_r($toParse);
die();
$toParse=str_replace("\r\n    ","",$toParse[0]);
$toParse=str_replace("\r\n  ","",$toParse);
$toParse=str_replace("\r\n","",$toParse);
$toParse=explode("</tr><tr>",$toParse);
// The above is to get the key part of the original page, the following is to parse the data in detail

// $dateData comes from the first part of $toParse, it stores the dates in current week, starting from Monday

$dateData=str_replace("<th width=\"17%\" align=\"center\" class=\"C-tableA5\">","",$toParse[0]);
$dateData=explode("</th>",$dateData);

// remove name of day and to keep only the date part
foreach($dateData as $key=>$value){
	if($value!=""){
		$dateData[$key]=explode("<br>",$value);
		$dateData[$key]=$dateData[$key][1];
	}else{
		// There is an existence of empty $dateData[5], to prevent error, we'll unset that array element
		unset($dateData[$key]);
	}
}

//print_r(
print_r($dateData);
for($i=0; $i<7; $i++){
$toParse[$i]=str_replace("</td>","",$toParse[$i]);
$toParse[$i]=explode("<td align=\"center\" class=\"C-tableA2\">",$toParse[$i]);
}

print_r($toParse);

$lunchData=array(6);

foreach($lunchData as $key=>$value){
	$luchData[$key]=array(8);
}
//$lunchData[0]
for($i=1; $i<=5; $i++){
	$str="SELECT date FROM lunch WHERE date='".$dateData[$i-1]."';";
	$result=qMysql($str);
	$record=mysql_fetch_array($result);
	if($record[0]==''){
		// Monday is located at index 0 of $dateData ,but located at index 1 of $lunchData, this loop starts from 1, which indicates that $i should be minus 1 before used as index of $dateData
		$query = "INSERT INTO `lunch`(`date`,`day`,`week`,`base`,`main`,`secd`,`soup`,`fruit`,`calories`) VALUES('".$dateData[$i-1]."',".$i.",WEEK('".$dateData[$i-1]."','1')";
		for($j=1; $j<=6; $j++){
			// first index of $luchData is column, second is about day
			$query = $query.",'".$toParse[$j][$i]."'";
		}
		$query = $query.")";
		echo $query;
		qMysql($query);
		echo "\n";
	}
}
?>
