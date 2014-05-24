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

//$query = "SELECT * FROM lunch WHERE date = '".date("Y/m/d")."'";
$query = "SELECT * FROM lunch WHERE date = '2014/05/23'";
$result = mysql_fetch_assoc(qMysql($query));
echo json_encode($result, JSON_PRETTY_PRINT);

?>
