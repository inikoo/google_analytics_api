<?php
/**
 * Created by PhpStorm.
 * User: sasi
 * Date: 07/04/2019
 * Time: 1:26
 */

require_once __DIR__ . '/vendor/autoload.php';

$servername = "localhost";
$username = "admin";
$password = "PW4admin";
$dbname = "google";

function initializeAnalytics()
{
    $KEY_FILE_LOCATION = __DIR__ . '/secret.json';

    $client = new Google_Client();
    $client->setApplicationName("Aurora Analytics");
    $client->setAuthConfig($KEY_FILE_LOCATION);
    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
    $analytics = new Google_Service_AnalyticsReporting($client);

    return $analytics;
}

function getReport($analytics) {
    $au_date_from = date("Y-m-d",strtotime("-7 days"));
    $au_date_to = date("Y-m-d",strtotime("-1 days"));

    $VIEW_ID = '152602933';

    $dateRange = new Google_Service_AnalyticsReporting_DateRange();
    $dateRange->setStartDate($au_date_from);
    $dateRange->setEndDate($au_date_to);

    $pageviews = new Google_Service_AnalyticsReporting_Metric();
    $pageviews->setExpression('ga:pageviews');
    $pageviews->setAlias('Pageviews');
    $pageValue = new Google_Service_AnalyticsReporting_Metric();
    $pageValue->setExpression("ga:pageValue");
    $pageValue->setAlias("Page Value");
    $users = new Google_Service_AnalyticsReporting_Metric();
    $users->setExpression("ga:users");
    $users->setAlias("Users");
    $sessions = new Google_Service_AnalyticsReporting_Metric();
    $sessions->setExpression("ga:sessions");
    $sessions->setAlias("Sessions");

    $pagePath = new Google_Service_AnalyticsReporting_Dimension();
    $pagePath->setName("ga:pagePath");


    $request = new Google_Service_AnalyticsReporting_ReportRequest();
    $request->setViewId($VIEW_ID);
    $request->setDateRanges($dateRange);
    $request->setDimensions(array($pagePath));
    $request->setMetrics(array($pageviews,$pageValue,$users,$sessions));

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests(array($request));
    return $analytics->reports->batchGet( $body );
}

$conn = new mysqli($servername, $username, $password, $dbname);

$analytics = initializeAnalytics();
$response = getReport($analytics);
/*$report = $response[0];*/
$rows = $response[0]->getData()->getRows();

$sql =  "";
foreach ($rows as $r) {
    $dimensions = $r->getDimensions();
    $metrics = $r->getMetrics();
    $values = $metrics[0]->getValues();

    $sql .=  "INSERT INTO `Webpage Analytics Data` (`Webpage Analytics Webpage Key`, `Webpage Analytics Total Acc Pageviews`, `Webpage Analytics Total Acc Page Value`, `Webpage Analytics 1 Year Acc Users`, `Webpage Analytics Total Acc Sessions`)
VALUES ((SELECT IFNULL((SELECT `Page Key` FROM `Page Store Dimension` WHERE `Webpage URL` ='https://www.awgifts.eu".$dimensions[0]."') ,'0')) ,'".$values[0]."' ,'". $values[1] ."' ,'".$values[2]."' ,'".$values[3]."' )
ON DUPLICATE KEY UPDATE `Webpage Analytics Total Acc Pageviews` = '". $values[0] ."', `Webpage Analytics Total Acc Page Value` = '". $values[1] ."', `Webpage Analytics 1 Year Acc Users` = '". $values[2] ."', `Webpage Analytics Total Acc Sessions` = '". $values[3] ."';";
}
$sql .= "DELETE FROM `Webpage Analytics Data` WHERE `Webpage Analytics Webpage Key` NOT IN ( SELECT `Page Key` FROM `Page Store Dimension`);";

if ($conn->multi_query($sql) === TRUE) {
    echo "Google Analytics data updated successfully" . "\n";

} else {
    echo "Error: ". "\n" . $sql . "\n" . $conn->error . "\n";
}


$conn->close();
