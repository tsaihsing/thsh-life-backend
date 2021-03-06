<?php

session_start();
require 'config.php';
require 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->response->headers->set("Content-Type", "application/json; charset=utf-8");
$app->response->headers->set("Access-Control-Allow-Origin", "*");

// Set up database connection

R::setup('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);

$app->get('/meal', function(){

  $askedDate = date("Y/m/d");

  if(isset($_GET['date'])&&$_GET['date']!=''){
    if(strpos($_GET['date'], " ") != false){
      $date_split = explode(" ", $_GET['date']);
      $askedDate = $date_split[0];
    }else{
      $askedDate = $_GET['date'];
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

  $result = R::getRow("SELECT * FROM lunch WHERE date = ? AND type = ?", [$askedDate, $type]);
  echo json_encode($result);
});

$app->get('/schoolbus', function(){

  if(isset($_GET['list'])){

    $query = 'SELECT `driver` FROM `schoolbus` WHERE `utime` >= CURDATE() GROUP BY `driver`';

    if(TEST_MODE){
      $query = 'SELECT `driver` FROM `schoolbus` WHERE 1 GROUP BY `driver`';
    }

    $result = R::getAll($query);

    echo json_encode($result, JSON_PRETTY_PRINT);

  }else{

    if(isset($_GET['bus'])){
      if(TEST_MODE){
        include('schoolbus_proto.php');
      }else{
        $query = 'SELECT `driver` as bus, `lat`, `long`, `state`, `speed`, `utime`, `direction` FROM `schoolbus` WHERE `driver` = ? ORDER BY `utime` DESC LIMIT 0,1';
        $result = R::getRow($query, [ $_GET['bus'] ]);
        echo json_encode($result, JSON_PRETTY_PRINT);
      }
    }

  }
});

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

        R::exec("INSERT INTO `lunch`(`date`,`day`,`week`,`base`,`main`,`secd`,`soup`,`fruit`,`calories`,`type`) VALUES(?, ?, WEEK(?, '1'), ?, ?, ?, ?, ?, ?, ?)", $values);
      }
    }

  }
});

$app->post('/fetch/schoolbus', function() use($app) {

  if($_SERVER['REMOTE_ADDR'] != FETCHER_IP){
    $app->halt(403, "Forbidden");
  }

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

    $query = "INSERT INTO `schoolbus`(`plate`,`driver`,`speed`,`utime`,`lat`,`long`,`state`,`direction`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";

    R::exec($query, array(
      $status[0][0],
      $status[0][1],
      $status[1],
      $status[2],
      $position[0],
      $position[1],
      $state,
      $direction[1]
    ));

  }
});

$app->get('/weather', function(){

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

  echo json_encode($weather, JSON_PRETTY_PRINT);

});

$app->get('/contact_book/list', function(){

  define("CLASSLISTURI", "http://210.71.64.9/CustomerSet/018_ContactBooks/u_list_v.asp?id={19D75159-18A8-4612-ABAD-26400DA80A29}");

  $toParse = file_get_contents(CLASSLISTURI);
  $toParse = explode('<select id="delClassSearch" name="delClassSearch"><option value="0"  selected>請選擇班級</option><option value="', $toParse);
  $toParse = explode('</option></select>', $toParse[1]);
  $toParse = explode('</option><option value="', $toParse[0]);

  $class_all = array();

  foreach($toParse as $class){

    $class_data = explode('">', $class);
    $grade = mb_substr($class_data[1], 0, 2, "utf-8");

    if(!isset($class_all[$grade])){
      $class_all[$grade] = array();
    }

    $class_all[$grade][] = array($class_data[0], mb_substr($class_data[1], 2, 4, "utf-8"));

  }

  $class_final = array();

  foreach($class_all as $key => $value){

    $class_final[] = array($key, $value);

  }

  echo json_encode($class_final);

});

$app->get('/contact_book', function(){

  define("CBURI", "http://210.71.64.9/CustomerSet/018_ContactBooks/u_Detail.asp?id={19D75159-18A8-4612-ABAD-26400DA80A29}");

  $askedDate = date("Y/m/d");

  if(isset($_GET['date'])&&$_GET['date']!=''){
    if(strpos($_GET['date'], " ") != false){
      $date_split = explode(" ", $_GET['date']);
      $askedDate = $date_split[0];
    }
  }

  $toParse = file_get_contents(CBURI.'&cid='.$_GET['cid'].'&sdate='.$askedDate);
  $toParse = str_replace("\r\n", "", $toParse);
  $toParse = explode('<form name="form1" method="post" action="u_list_v.asp?id={19D75159-18A8-4612-ABAD-26400DA80A29}&pageno=&mode=">', $toParse);
  $toParse = explode('</table>', $toParse[1]);

//  $toParse = explode('<td align="center"', $toParse[0]);

  echo($toParse[0]);

});

$app->get('/calendar', function(){

  define("CALENDAR_URL", "http://www.thsh.tp.edu.tw/calendar/pagecalendar.asp?id={B8CD5507-86D8-4661-8D92-40AB2D5C6584}&mode=view&seqno=1");

  $toParse = file_get_contents(CALENDAR_URL);
  $toParse = str_replace("\n", "", $toParse);
  $toParse = str_replace("\r", "", $toParse);
  $toParse = explode('→</a></td>   </tr> </table></td></tr>', $toParse);
  $toParse = explode('</form><tr class="C-tableA1"> <td align="center"', $toParse[1])[0];
  $toParse = strip_tags($toParse, "<tr><th><td>");
  $toParse = str_replace('▲', "", $toParse);
  echo json_encode(array("data" => $toParse));
});

$app->get('/news', function(){

  define("NEWSURL", "http://www.thsh.tp.edu.tw/news_under/u_news_v.asp?id={02E6CF60-BCF3-489C-923D-0A0A155CE672}");

  $toParse = file_get_contents(NEWSURL);
  $toParse = explode('<CAPTION class="C-tableA-captton">下級訊息發布列表</CAPTION>', $toParse);
  unset($toParse[0]);
  foreach($toParse as $key => $value){
    $toParse[$key] = explode('</tr>', $value);
  }
  $data = array();
  for($i = 1; $i <= 10; $i++){
    $toParse[1][$i] = str_replace("</a></td>", "", $toParse[1][$i]);
    $toParse[1][$i] = str_replace("</td>", "", $toParse[1][$i]);
    $toParse[1][$i] = str_replace("\r", "", $toParse[1][$i]);
    $toParse[1][$i] = str_replace("\n", "", $toParse[1][$i]);
    $toParse[1][$i] = explode('<td align="left">', $toParse[1][$i]);
    $toParse[1][$i][1] = explode('"', $toParse[1][$i][1]);
    $toParse[1][$i][0] = $toParse[1][$i][1][1];
    $toParse[1][$i][1] = substr($toParse[1][$i][1][6], 1);
    if(strpos("<font", $toParse[1][$i][1]) != -1){
      $toParse[1][$i][1] .= "</font>";
    }
    $data[] = $toParse[1][$i];
  }
  echo json_encode($data);
});

$app->get('/news/u_news_v3.asp', function() use($app) {

  $required_parameters = array('id', 'newsid');
  foreach($required_parameters as $para){
    if(!isset($_GET[$para])){
      $app->halt(400);
    }
  }

  define("NEWSDETAILURL", "http://www.thsh.tp.edu.tw/news/u_news_v3.asp?id=".$_GET['id']."&newsid=".$_GET['newsid']);
  $toParse = file_get_contents(NEWSDETAILURL);
  $toParse = explode('列表</CAPTION>', $toParse);
  if(sizeof($toParse) == 1){
    $app->halt(404);
  }
  $toParse = explode('</table>', $toParse[1])[0];
  // fix youtube problem, since cordova app is running at file://
  $toParse = str_replace('src="//', 'src="https://', $toParse);
  $toParse = str_replace('(點選連結時，會以開新視窗方式呈現)', '', $toParse);
  $toParse = str_replace('<th', '<!-- <th', $toParse);
  $toParse = str_replace('</th>', '</th> --><br />', $toParse);
  $toParse = strip_tags($toParse, "<img><font><a><br><iframe>");
  echo json_encode(array("data" => $toParse));
});

$app->get("/:legacy", function($legacy) use($app) {

  // This is only to keep CRON jobs work

  $legacy_map = array(
    "fetch_lunch.php" => 'fetch/meal'
  );

  if(isset($legacy_map[$legacy])){
    $app->redirect($legacy_map[$legacy]);
  }

});

$app->run();

?>
