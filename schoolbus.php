<?php

require('db.inc.php');

function listActiveBus(){
	// list active cars today
	$query = 'SELECT `driver` FROM `schoolbus` WHERE `utime` >= CURDATE() GROUP BY `driver`';
	$result = qMysql($query);
	$rows = array();
	while($row = mysql_fetch_row($result)){
		$rows[] = $row[0];
	}
	return $rows;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

if(isset($_GET['list'])){
	echo json_encode(listActiveBus(), JSON_PRETTY_PRINT);
	exit();
}

if(isset($_GET['bus'])){
	$activeBuses = listActiveBus();
	$bus = strval($_GET['bus']);
	// check if asked bus is active today
	if(in_array($bus, $activeBuses, true)){
		$query = 'SELECT `lat`, `long`, `state`, `speed`, `utime`, `direction` FROM `schoolbus` WHERE `driver` = \''.$bus.'\' ORDER BY `utime` DESC LIMIT 0,1';
		$result = qMysql($query);
		echo json_encode(mysql_fetch_assoc($result), JSON_PRETTY_PRINT);
	}else{
		// invalid bus
	}
}
?>