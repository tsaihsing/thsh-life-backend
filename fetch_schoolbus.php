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

if($_SERVER['REMOTE_ADDR'] != '127.0.0.1'){
  //die();
//  echo "remote\n";
}

print_r($_POST);

if(isset($_POST['status'])&&isset($_POST['position'])){
  $status = json_decode($_POST['status']);
  $position = json_decode($_POST['position']);

  print_r($status);
  print_r($position);
}

?>
