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

if($_SERVER['REMOTE_ADDR'] != '192.168.50.85'){
  // restrict to local access only
  header('HTTP/1.1 403 Forbidden');
  die();
}

print_r($_POST);

if(isset($_POST['status'])&&isset($_POST['position'])&&isset($_POST['direction'])){
  $status = json_decode($_POST['status']);
  $position = json_decode($_POST['position']);

  // split into plate and driver
  $status[0] = explode(',', $status[0]);
  // remove km/h text
  $status[1] = abs(explode(',', $status[1])[0]);
  // filter direction
  $direction = preg_replace("/[^a-zA-z0-9]/", "", $_POST['direction']);
  $state = 0;
  if($direction[0]=='D'){
	// if car is green, means it's running
	$state = 1;
  }
  print_r($status);
  print_r($position);

  $query = "INSERT INTO `schoolbus`(`plate`,`driver`,`speed`,`utime`,`lat`,`long`,`state`,`direction`) VALUES('".$status[0][0]."','".$status[0][1]."','".$status[1]."','".$status[2]."','".$position[0]."','".$position[1]."','".$state."','".$direction[1]."')";
  // echo $query;
  qMysql($query);
}

?>
