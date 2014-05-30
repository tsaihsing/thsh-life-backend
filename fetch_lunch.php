<?php

require('db.inc.php');

$mealLinks = array('http://210.71.64.9/school_lunch/lunch01.asp?id=%7B10719F54-A185-4776-8B54-36241B455285%7D','http://210.71.64.9/school_lunch/lunch01.asp?id=%7b747CE11F-D618-4AB2-A76C-57795010975D%7d','http://210.71.64.9/school_lunch/lunch01.asp?id=%7BF66638B7-90C2-448D-98E4-42D4EB10DC4D%7D','http://210.71.64.9/school_lunch/lunch01.asp?id=%7BD72654DF-6FC1-43DF-BC44-05E5B8CAD481%7D');

foreach($mealLinks as $type => $link){
	$mealLink = $link."&lunchdate=".date("Y/m/d");
	$toParse=file_get_contents($mealLink);
	$toParse=explode("<th width=\"15%\" align=\"center\" class=\"C-tableA5\">項目\\日期</th>",$toParse);
	$toParse=explode("\r\n  <tr>\r\n    <td colspan=\"6\" align=\"center\" class=\"C-tableA1\">宣導事項</td>\r\n  </tr>\r\n  <tr>",$toParse[1]);
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
		$str="SELECT date FROM lunch WHERE date='".$dateData[$i-1]."' AND type = $type;";
		$result=qMysql($str);
		$record=mysql_fetch_array($result);
		if($record[0]==''){
			// Monday is located at index 0 of $dateData ,but located at index 1 of $lunchData, this loop starts from 1, which indicates that $i should be minus 1 before used as index of $dateData
			$query = "INSERT INTO `lunch`(`date`,`day`,`week`,`base`,`main`,`secd`,`soup`,`fruit`,`calories`,`type`) VALUES('".$dateData[$i-1]."',$i,WEEK('".$dateData[$i-1]."','1')";
			for($j=1; $j<=6; $j++){
				// first index of $luchData is column, second is about day
				$query = $query.",'".$toParse[$j][$i]."'";
			}
			$query = $query.",$type)";
			// echo $query;
			qMysql($query);
			//echo "\n";
		}
	}

}
?>