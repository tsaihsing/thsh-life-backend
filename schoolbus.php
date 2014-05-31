<?php

$testMode = true;

require('db.inc.php');

function listActiveBus($testMode){
	// list active cars today
	$query = 'SELECT `driver` FROM `schoolbus` WHERE `utime` >= CURDATE() GROUP BY `driver`';

if($testMode == true){
	$query = 'SELECT `driver` FROM `schoolbus` WHERE 1 GROUP BY `driver`';
}

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
	echo json_encode(listActiveBus($testMode), JSON_PRETTY_PRINT);
	exit();
}

if(isset($_GET['bus'])){
	if($testMode == true){
		include('schoolbus_proto.php');
		exit();
	}
	$activeBuses = listActiveBus($testMode);
	$bus = strval($_GET['bus']);
	// check if asked bus is active today
	if(in_array($bus, $activeBuses, true)){
		$query = 'SELECT `driver` as bus, `lat`, `long`, `state`, `speed`, `utime`, `direction` FROM `schoolbus` WHERE `driver` = \''.$bus.'\' ORDER BY `utime` DESC LIMIT 0,1';
		$result = mysql_fetch_assoc(qMysql($query));
		echo json_encode($result, JSON_PRETTY_PRINT);
	}else{
		// invalid bus
	}
}
?>