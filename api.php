<?php


require_once __DIR__ . '/vendor/autoload.php';

$KEY_FILE_LOCATION = __DIR__ . '/secret.json';

$servername = "localhost";
$username = "admin";
$password = "PW4admin";
$dbname = "google";

function initializeAnalytics($KEY) {

    $client = new Google_Client();
    $client->setApplicationName("Aurora Analytics");
    $client->setAuthConfig($KEY);
    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
    $analytics = new Google_Service_AnalyticsReporting($client);

    return $analytics;
}

function initializeSearch($KEY) {

    $client = new Google_Client();
    $client->setApplicationName("Aurora Search Console");
    $client->setAuthConfig($KEY);
    $client->setScopes(['https://www.googleapis.com/auth/webmasters.readonly']);
    $webmastersService = new Google_Service_Webmasters($client);

    return $webmastersService;
}

$property_arr = array(
    "ao" => "www.agnesandcat.org",
    "aw" => "www.ancientwisdom.biz",
    "ac" => "www.aw-cadeaux.com",
    "ag" => "www.aw-geschenke.com",
    "ap" => "www.aw-podarki.com",
    "ar" => "www.aw-regali.com",
    "at" => "www.awgifts.at",
    "cz" => "www.awgifts.cz",
    "eu" => "www.awgifts.eu",
    "fr" => "www.awgifts.fr",
    "pl" => "www.awgifts.pl",
    "sk" => "www.awgifts.sk");

$date_from = date("Y-m-d",strtotime("-7 days"));
$date_to = date("Y-m-d",strtotime("-1 days"));
$yesterday_to = date("Y-m-d",strtotime("-1 days"));

function getReport($analytics,$website,$au_date_from, $au_date_to) {

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

    $hostname = new Google_Service_AnalyticsReporting_Dimension();
    $hostname->setName("ga:hostname");
    $pagePath = new Google_Service_AnalyticsReporting_Dimension();
    $pagePath->setName("ga:pagePath");

    // Create the segment dimension.
    $segmentDimensions = new Google_Service_AnalyticsReporting_Dimension();
    $segmentDimensions->setName("ga:segment");

    // Create Dimension Filter.
    $dimensionFilter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
    $dimensionFilter->setDimensionName("ga:hostname");
    $dimensionFilter->setOperator("EXACT");
    $dimensionFilter->setExpressions(array($website));

    // Create Segment Filter Clause.
    $segmentFilterClause = new Google_Service_AnalyticsReporting_SegmentFilterClause();
    $segmentFilterClause->setDimensionFilter($dimensionFilter);

    // Create the Or Filters for Segment.
    $orFiltersForSegment = new Google_Service_AnalyticsReporting_OrFiltersForSegment();
    $orFiltersForSegment->setSegmentFilterClauses(array($segmentFilterClause));

    // Create the Simple Segment.
    $simpleSegment = new Google_Service_AnalyticsReporting_SimpleSegment();
    $simpleSegment->setOrFiltersForSegment(array($orFiltersForSegment));

    // Create the Segment Filters.
    $segmentFilter = new Google_Service_AnalyticsReporting_SegmentFilter();
    $segmentFilter->setSimpleSegment($simpleSegment);

    // Create the Segment Definition.
    $segmentDefinition = new Google_Service_AnalyticsReporting_SegmentDefinition();
    $segmentDefinition->setSegmentFilters(array($segmentFilter));

    // Create the Dynamic Segment.
    $dynamicSegment = new Google_Service_AnalyticsReporting_DynamicSegment();
    $dynamicSegment->setSessionSegment($segmentDefinition);
    $dynamicSegment->setName("website");

    // Create the Segments object.
    $segment = new Google_Service_AnalyticsReporting_Segment();
    $segment->setDynamicSegment($dynamicSegment);


    $request = new Google_Service_AnalyticsReporting_ReportRequest();
    $request->setViewId($VIEW_ID);
    $request->setDateRanges($dateRange);
    $request->setDimensions(array($hostname,$pagePath,$segmentDimensions));
    $request->setSegments(array($segment));
    $request->setMetrics(array($pageviews,$pageValue,$users,$sessions));
    $request->setPageSize(10000);

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests(array($request));
    return $analytics->reports->batchGet( $body );
}

function getSearchReport($webmastersService,$website,$au_date_from, $au_date_to) {

    $query = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
    $query->setDimensions(array('page'));
    $query->setStartDate($au_date_from);
    $query->setEndDate($au_date_to);
    $query->setRowLimit(5000);

    /*$filter = new Google_Service_Webmasters_ApiDimensionFilter();
    $filter->setDimension('page');
    $filter->setOperator('equals');
    $filter->setExpression(array($result->fetch_assoc()));

    $filterGroup = new Google_Service_Webmasters_ApiDimensionFilterGroup();
    $filterGroup->setFilters(array($filter));
    $query->setDimensionFilterGroups(array($filterGroup));*/

    $report = $webmastersService->searchanalytics->query('https://'.$website.'/', $query);
    return $report;
}
function getQueryReport($webmastersService,$website,$au_date_from, $au_date_to) {

    $query = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
    $query->setDimensions(array('page','query'));
    $query->setStartDate($au_date_from);
    $query->setEndDate($au_date_to);
    $query->setRowLimit(5000);

    $report = $webmastersService->searchanalytics->query('https://'.$website.'/', $query);
    return $report;
}
function dbReportInsert($rows,$responseSearch,$responseQuery,$rowsDaily,$responseDailySearch,$apiCallId,$date_to)
{
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);

    $sql = "";
    foreach ($rows as $r) {
        $dimensions = $r->getDimensions();
        $metrics = $r->getMetrics();
        $values = $metrics[0]->getValues();
        $hostname = $dimensions[0];
        $pagePath = $dimensions[1];
        $path = parse_url($pagePath, PHP_URL_PATH);
        $pageviews = $values[0];
        $pageValue = $values[1];
        $users = $values[2];
        $sessions = $values[3];

        $sql .= "INSERT IGNORE INTO `Google Webpage`(`Google Webpage URL`, `Google Webpage Website`, `Google Webpage Original Path`, `Google Webpage Canonical Path`) VALUES ('https://".$hostname.$pagePath."' ,'$hostname' ,'$pagePath' ,'$path');
            INSERT INTO `Google Data`(`Google API Call Key`, `Google Webpage Key`, `Google 1 Week Acc Page Value`, `Google 1 Week Acc Pageviews`, `Google 1 Week Acc Sessions`, `Google 1 Week Acc Users`) VALUES ('$apiCallId',(SELECT `Google Webpage Key` FROM `Google Webpage` WHERE `Google Webpage URL` = 'https://".$hostname.$pagePath."') ,'$pageValue' ,'$pageviews' ,'$sessions' ,'$users') ON DUPLICATE KEY UPDATE `Google 1 Week Acc Page Value` = '$pageValue', `Google 1 Week Acc Pageviews` = '$pageviews', `Google 1 Week Acc Sessions` = '$sessions', `Google 1 Week Acc Users` = '$users' ;";
    }
    foreach ($responseSearch as $r) {
        $hostname = parse_url($r->keys[0], PHP_URL_HOST);
        $path = parse_url($r->keys[0], PHP_URL_PATH);
        $query = parse_url($r->keys[0], PHP_URL_QUERY) !== null ? '?' . parse_url($r->keys[0], PHP_URL_QUERY) : '';
        $fragment = parse_url($r->keys[0], PHP_URL_FRAGMENT) !== null ? '#' . parse_url($r->keys[0], PHP_URL_FRAGMENT) : '';

        $sql .= "INSERT IGNORE INTO `Google Webpage`(`Google Webpage URL`, `Google Webpage Website`, `Google Webpage Original Path`, `Google Webpage Canonical Path`) VALUES ('".$r->keys[0]."' ,'$hostname' ,'".$path.$query.$fragment."' ,'$path');
            INSERT INTO `Google Data`(`Google API Call Key`, `Google Webpage Key`, `Google 1 Week Acc SC Clicks`, `Google 1 Week Acc SC CTR`, `Google 1 Week Acc SC Impressions`, `Google 1 Week Acc SC Position`) VALUES ('$apiCallId',(SELECT `Google Webpage Key` FROM `Google Webpage` WHERE `Google Webpage URL` = '".$r->keys[0]."'),'$r->clicks' ,'$r->ctr' ,'$r->impressions' , '$r->position') ON DUPLICATE KEY UPDATE `Google 1 Week Acc SC Clicks` = '$r->clicks', `Google 1 Week Acc SC CTR` = '$r->ctr', `Google 1 Week Acc SC Impressions` = '$r->impressions', `Google 1 Week Acc SC Position` = '$r->position' ;";
    }
    foreach ($responseQuery as $q) {
        $hostname = parse_url($q->keys[0], PHP_URL_HOST);
        $path = parse_url($q->keys[0], PHP_URL_PATH);
        $query = parse_url($q->keys[0], PHP_URL_QUERY) !== null ? '?' . parse_url($q->keys[0], PHP_URL_QUERY) : '';
        $fragment = parse_url($q->keys[0], PHP_URL_FRAGMENT) !== null ? '#' . parse_url($q->keys[0], PHP_URL_FRAGMENT) : '';

        $sql .= "INSERT IGNORE INTO `Google Webpage`(`Google Webpage URL`, `Google Webpage Website`, `Google Webpage Original Path`, `Google Webpage Canonical Path`) VALUES ('".$q->keys[0]."' ,'$hostname' ,'".$path.$query.$fragment."' ,'$path');
            INSERT INTO `Google Query Data` (`Google API Call Key`, `Google Webpage Key`, `Google Query`, `Google 1 Week Acc Clicks`, `Google 1 Week Acc CTR`, `Google 1 Week Acc Impressions`, `Google 1 Week Acc Position`) VALUES ('$apiCallId',(SELECT `Google Webpage Key` FROM `Google Webpage` WHERE `Google Webpage URL` = '".$q->keys[0]."'),'".$q->keys[1]."','$q->clicks' ,'$q->ctr' ,'$q->impressions' , '$q->position') ON DUPLICATE KEY UPDATE `Google 1 Week Acc Clicks` = '$q->clicks', `Google 1 Week Acc CTR` = '$q->ctr', `Google 1 Week Acc Impressions` = '$q->impressions', `Google 1 Week Acc Position` = '$q->position';";
    }
    foreach ($rowsDaily as $rd) {
        $dimensions = $rd->getDimensions();
        $metrics = $rd->getMetrics();
        $values = $metrics[0]->getValues();
        $hostname = $dimensions[0];
        $pagePath = $dimensions[1];
        $path = parse_url($pagePath, PHP_URL_PATH);
        $pageviews = $values[0];
        $pageValue = $values[1];
        $users = $values[2];
        $sessions = $values[3];

        $sql .= "INSERT IGNORE INTO `Google Webpage`(`Google Webpage URL`, `Google Webpage Website`, `Google Webpage Original Path`, `Google Webpage Canonical Path`) VALUES ('https://".$hostname.$pagePath."' ,'$hostname' ,'$pagePath' ,'$path');
            INSERT INTO `Google Time Series`(`Google API Call Key`, `Google Webpage Key`, `Google Time Series Date`, `Google Time Series Page Value`, `Google Time Series Pageviews`, `Google Time Series Sessions`, `Google Time Series Users`) VALUES ('$apiCallId',(SELECT `Google Webpage Key` FROM `Google Webpage` WHERE `Google Webpage URL` = 'https://".$hostname.$pagePath."') ,'$date_to' ,'$pageValue' ,'$pageviews' ,'$sessions' ,'$users') ON DUPLICATE KEY UPDATE `Google Time Series Page Value` = '$pageValue', `Google Time Series Pageviews` = '$pageviews', `Google Time Series Sessions` = '$sessions', `Google Time Series Users` = '$users' ;";
    }
    foreach ($responseDailySearch as $rq) {
        $hostname = parse_url($rq->keys[0], PHP_URL_HOST);
        $path = parse_url($rq->keys[0], PHP_URL_PATH);
        $query = parse_url($rq->keys[0], PHP_URL_QUERY) !== null ? '?' . parse_url($rq->keys[0], PHP_URL_QUERY) : '';
        $fragment = parse_url($rq->keys[0], PHP_URL_FRAGMENT) !== null ? '#' . parse_url($rq->keys[0], PHP_URL_FRAGMENT) : '';

        $sql .= "INSERT IGNORE INTO `Google Webpage`(`Google Webpage URL`, `Google Webpage Website`, `Google Webpage Original Path`, `Google Webpage Canonical Path`) VALUES ('".$rq->keys[0]."' ,'$hostname' ,'".$path.$query.$fragment."' ,'$path');
            INSERT INTO `Google Time Series`(`Google API Call Key`, `Google Webpage Key`, `Google Time Series Date`, `Google Time Series Clicks`, `Google Time Series CTR`, `Google Time Series Impressions`, `Google Time Series Position`) VALUES ('$apiCallId',(SELECT `Google Webpage Key` FROM `Google Webpage` WHERE `Google Webpage URL` = '".$rq->keys[0]."') ,'$date_to' ,'$rq->clicks' ,'$rq->ctr' ,'$rq->impressions' , '$rq->position') ON DUPLICATE KEY UPDATE `Google Time Series Clicks` = '$rq->clicks', `Google Time Series CTR` = '$rq->ctr', `Google Time Series Impressions` = '$rq->impressions', `Google Time Series Position` = '$rq->position' ;";
    }
    if ($conn->multi_query($sql) === TRUE) {
        echo "Google Analytics and Search Console data updated successfully" . "\n";
    } else {
        echo "Error: " . "\n" . $sql . "\n" . $conn->error . "\n";
    }
    $conn->close();
}

$link = new mysqli($servername, $username, $password, $dbname);

$executionSqlStart = "INSERT INTO `Google API Call Dimension` (`Google API Call Start Date`, `Google API Call ID`) VALUES ('".date("Y-m-d H:i:s",time())."','{\"".$date_from.":".$date_to."\":[]}');";
mysqli_query($link, $executionSqlStart);
$apiCallId = mysqli_insert_id($link);
echo "Start Time :".date("Y-m-d  H:i:s",time()). "\n";
echo "API Call ID :".$apiCallId. "\n";
foreach ($property_arr as $k => $pa){

    $analytics = initializeAnalytics($KEY_FILE_LOCATION);
    $response = getReport($analytics,$pa,$date_from,$date_to);
    $rows = $response[0]->getData()->getRows();
    $gaRowCount = $response[0]->getData()->getRowCount();
    $gaToken = $response[0]->getNextPageToken();
    $responseDaily = getReport($analytics,$pa,$date_to,$yesterday_to);
    $rowsDaily = $responseDaily[0]->getData()->getRows();
    $gaDailyRowCount = $responseDaily[0]->getData()->getRowCount();
    $gaDailyToken = $responseDaily[0]->getNextPageToken();

    $webmastersService = initializeSearch($KEY_FILE_LOCATION);
    $responseSearch = getSearchReport($webmastersService,$pa,$date_from,$date_to);
    $scRowCount = count($responseSearch->rows);
    $responseQuery = getQueryReport($webmastersService,$pa,$date_from,$date_to);
    $sqRowCount = count($responseQuery->rows);
    $responseDailySearch = getSearchReport($webmastersService,$pa,$date_to,$yesterday_to);
    $scDailyRowCount = count($responseDailySearch->rows);

        dbReportInsert($rows,$responseSearch,$responseQuery,$rowsDaily,$responseDailySearch,$apiCallId,$date_to);

    $executionSqlUpdate = "UPDATE google.`Google API Call Dimension` SET `Google API Call ID` = JSON_INSERT(`Google API Call ID` ,'$.\"".$date_from.":".$date_to."\"[9999]' ,JSON_OBJECT('ga$k',JSON_OBJECT('rows','$gaRowCount','token','$gaToken'),'sc$k',JSON_OBJECT('rows','$scRowCount'),'sq$k',JSON_OBJECT('rows','$sqRowCount'))) WHERE `Google API Call Key` = '$apiCallId';";


mysqli_query($link, $executionSqlUpdate);
}
$executionSqlEnd = "UPDATE `Google API Call Dimension` SET `Google API Call End Date` = '".date("Y-m-d  H:i:s",time())."' WHERE `Google API Call Key` = '$apiCallId';";
mysqli_query($link, $executionSqlEnd);
echo "End Time :".date("Y-m-d  H:i:s",time()). "\n";

$link->close();
/*
foreach ($rows as $r) {
    $dimensions = $r->getDimensions();
    $metrics = $r->getMetrics();
    $values = $metrics[0]->getValues();
    $hostname = $dimensions[0];
    $pagePath = $dimensions[1];
    $pageviews = $values[0];
    $pageValue = $values[1];
    $users = $values[2];
    $sessions = $values[3];
    printf("https://".$hostname.$pagePath."  |".$pageviews."  | ". $pageValue."  | ".$users."  | ".$sessions."  | ");
    printf("\n");
}

$Mask = "|%-50s |%-30s |%-30s |%-30s |%-30s";
printf($Mask ,'keys', 'impressions', 'clicks', 'ctr' ,'position');
printf("|\n");
foreach ($responseSearch as $r) {
    $page = $r->keys[0];
    $impressions = $r->impressions;
    $clicks = $r->clicks;
    $ctr = $r->ctr;
    $position = $r->position;
    printf($Mask ,$page ,$impressions ,$clicks ,$ctr , $position );
    printf("|\n");
}*/
