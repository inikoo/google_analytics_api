<?php
/**
 * Created by PhpStorm.
 * User: sasi
 * Date: 06/04/2019
 * Time: 12:05
 */


require_once __DIR__ . '/vendor/autoload.php';


$KEY_FILE_LOCATION = __DIR__ . '/secret.json';

$servername = "localhost";
$username = "admin";
$password = "PW4admin";
$dbname = "google";

$client = new Google_Client();
$client->setApplicationName("Aurora Search Console to DB");
$client->setAuthConfig($KEY_FILE_LOCATION);
$client->setScopes(['https://www.googleapis.com/auth/webmasters.readonly']);

$au_date = date("Y-m-d",strtotime("-7 days"));
$au_date_to = date("Y-m-d",strtotime("-1 days"));

$webmastersService = new Google_Service_Webmasters($client);

$query = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
$query->setDimensions(array('page'));
$query->setStartDate($au_date);
$query->setEndDate($au_date_to);

/*$filter = new Google_Service_Webmasters_ApiDimensionFilter();
$filter->setDimension('page');
$filter->setOperator('equals');
$filter->setExpression(array($result->fetch_assoc()));

$filterGroup = new Google_Service_Webmasters_ApiDimensionFilterGroup();
$filterGroup->setFilters(array($filter));
$query->setDimensionFilterGroups(array($filterGroup));*/

$conn = new mysqli($servername, $username, $password, $dbname);

$response = $webmastersService->searchanalytics->query('https://www.awgifts.eu/', $query);

$sql =  "";
foreach ($response->rows as $r) {
    $sql .=  "INSERT INTO `Webpage Analytics Data` 
    (`Webpage Analytics Webpage Key`, `Webpage Analytics Total Acc SC Impressions`, `Webpage Analytics Total Acc SC Clicks`, `Webpage Analytics Total Acc SC CTR`, `Webpage Analytics Total Acc SC Position`)
    VALUES ((SELECT IFNULL( (SELECT `Page Key` FROM `Page Store Dimension` WHERE `Webpage URL` = '". $r->keys[0] ."') ,'0') AS `Page Key`) ,'$r->impressions' ,'$r->clicks' ,'$r->ctr' , '$r->position' )
    ON DUPLICATE KEY UPDATE `Webpage Analytics Total Acc SC Impressions` = '$r->impressions', `Webpage Analytics Total Acc SC Clicks` = '$r->clicks',`Webpage Analytics Total Acc SC CTR` = '$r->ctr', `Webpage Analytics Total Acc SC Position` = '$r->position' ;";
}
$sql .= "DELETE FROM `Webpage Analytics Data` WHERE `Webpage Analytics Webpage Key` NOT IN ( SELECT `Page Key` FROM `Page Store Dimension`);";

if ($conn->multi_query($sql) === TRUE) {
    echo "Search Console data updated successfully" . "\n";

} else {
    echo "Error: ". "\n" . $sql . "\n" . $conn->error . "\n";
}


$conn->close();