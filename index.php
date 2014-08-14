<?php

session_start();
require 'db.config.php';
require 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->response->headers->set("Content-Type", "application/json; charset=utf-8");
$app->response->headers->set("Access-Control-Allow-Origin", "*");

// Set up database connection

R::setup('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);

$app->get('/fetch/meal', function(){
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

    for($i=0; $i<7; $i++){
    $toParse[$i]=str_replace("</td>","",$toParse[$i]);
    $toParse[$i]=explode("<td align=\"center\" class=\"C-tableA2\">",$toParse[$i]);
    }

    echo json_encode(array($dateData,$toParse));

    $lunchData=array(6);

    foreach($lunchData as $key=>$value){
      $luchData[$key]=array(8);
    }
    //$lunchData[0]

    for($i=1; $i<=5; $i++){

      $result = R::getRow("SELECT date FROM lunch WHERE date= ? AND type = ?", [ $dateData[$i-1], $type ]);

      if(empty($result)){

        // Monday is located at index 0 of $dateData ,but located at index 1 of $lunchData, this loop starts from 1, which indicates that $i should be minus 1 before used as index of $dateData

        $values = array($dateData[$i-1], $i, $dateData[$i-1]);
        for($j=1; $j<=6; $j++){
          // first index of $luchData is column, second is about day
          $values[] = $toParse[$j][$i];
        }
        $values[] = $type;

        R::exec("INSERT INTO `lunch`(`date`,`day`,`week`,`base`,`main`,`secd`,`soup`,`fruit`,`calories`,`type`) VALUES(?, ?, WEEK(?, '1'), ?, ?, ?, ?, ?, ?)", $values);
      }
    }

  }
});

$app->get("/:legacy", function($legacy) use($app) {

  $legacy_map = array(
    "fetch_lunch.php" => 'fetch/meal'
  );

  if(isset($legacy_map[$legacy])){
    $app->redirect($legacy_map[$legacy]);
  }

});

$app->run();

?>
