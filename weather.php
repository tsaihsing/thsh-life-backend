<?php

define("WEATHERURI","http://www.cwb.gov.tw/V7/forecast/town368/7Day/6300800.htm");

$toParse=file_get_contents(WEATHERURI);
$toParse=str_replace("<font color=#CC3366>","",$toParse);
$toParse=str_replace("</font>","",$toParse);
$toParse=str_replace(" ","",$toParse);
$toParse=str_replace("<br/>"," ",$toParse);
$toParse=str_replace(" style=\"min-width:72px;\"","",$toParse);
$toParse=str_replace("</tr>","",$toParse);
$toParse=str_replace("<td>","",$toParse);
$toParse=str_replace("<tdcolspan=\"2\">","",$toParse);
$toParse=str_replace("</td>","",$toParse);
$toParse=str_replace("","",$toParse);
$toParse=explode('<tdstyle="min-width:72px;"',$toParse);
foreach($toParse as &$value){
  $value = explode("\n", $value);
}

$weather = array();
$weather_src = array(2,3,4,5,9);

for($i = 1; $i <= 7; $i++){
	$k = $i - 1;
	$weather[$k][0] = $toParse[1][$i];
	for($j = 1; $j <=2; $j++){
		$weather[$k][$j] = array();
		foreach($weather_src as $src){
			if($src == 3){
				$weather_des = $toParse[$src][abs($i*2 + $j - 2)];
				$weather_des = explode('"', $weather_des);
				$weather[$k][$j][] = $weather_des[1];
				$weather[$k][$j][] = $weather_des[3];
			}else{
				$weather[$k][$j][] = $toParse[$src][abs($i*2 + $j - 2)];
			}
		}
	}
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

echo json_encode($weather, JSON_PRETTY_PRINT);
?>