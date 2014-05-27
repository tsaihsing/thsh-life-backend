<?php

function qMysql($str){
   $link = mysql_connect("192.168.50.201", "apps", "apps");
   if (!$link) {
    die('Could not connect: ' . mysql_error());
   }
   mysql_select_db("apps_lunch", $link) or die(mysql_error());
   mysql_query("SET NAMES 'utf8'");
   mysql_query("SET CHARACTER_SET_CLIENT=utf8");
   mysql_query("SET CHARACTER_SET_RESULTS=utf8");
   $result = mysql_query($str, $link) or die(mysql_error());
   mysql_close($link);
   return $result;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

$askedDate = date("Y/m/d");

if(isset($_GET['date'])&&$_GET['date']!=''){
  $dateArr = explode('/', $_GET['date']);
  if(sizeof($dateArr) == 3){
    $askedDate = abs($dateArr[0]).'/'.abs($dateArr[1]).'/'.abs($dateArr[2]);
  }
}

$type = 0;

if(isset($_GET['type'])){
	switch(strval($_GET['type'])){
		case 'breakfast':
			$type = 0;
			break;
		case 'lunch':
			$type = 1;
			break;
		case 'dinner':
			$type = 2;
			break;
		case 'snack':
			$type = 3;
			break;
	}
}

$query = "SELECT * FROM lunch WHERE date = '$askedDate' AND type = $type";
//$query = "SELECT * FROM lunch WHERE date = '2014/05/23'";
$result = mysql_fetch_assoc(qMysql($query));
echo json_encode($result, JSON_PRETTY_PRINT);

?>
