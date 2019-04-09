<?php


require_once __DIR__ . '/vendor/autoload.php';

$KEY_FILE_LOCATION = __DIR__ . '/secret.json';


function initializeAnalytics()
{
    global $KEY_FILE_LOCATION;

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


function initializeSearch()
{
    global $KEY_FILE_LOCATION;
    $client = new Google_Client();
    $client->setApplicationName("Aurora Search Console");
    $client->setAuthConfig($KEY_FILE_LOCATION);
    $client->setScopes(['https://www.googleapis.com/auth/webmasters.readonly']);
    $webmastersService = new Google_Service_Webmasters($client);

    return $webmastersService;
}

function getSearchReport($webmastersService)
{

    $au_date = date("Y-m-d", strtotime("-7 days"));
    $au_date_to = date("Y-m-d", strtotime("-1 days"));

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

    return $webmastersService->searchanalytics->query('https://www.awgifts.eu/', $query);
}

$analytics = initializeAnalytics();
$response = getReport($analytics);
$rows = $response[0]->getData()->getRows();

foreach ($rows as $r) {
    $dimensions = $r->getDimensions();
    $metrics = $r->getMetrics();
    $values = $metrics[0]->getValues();
    $dimension = $dimensions[0];
    $pageviews = $values[0];
    $pageValue = $values[1];
    $users = $values[2];
    $sessions = $values[3];
    printf($dimension."  |".$pageviews."  | ". $pageValue."  | ".$users."  | ".$sessions."  | ");
    printf("\n");
}

$webmastersService = initializeSearch();
$responseSearch = getSearchReport($webmastersService);

$Mask = "|%-50s |%-30s |%-30s |%-30s |%-30s";
printf($Mask ,'keys', 'impressions', 'clicks', 'ctr' ,'position');
printf("|\n");
foreach ($responseSearch->rows as $r) {
    $page = $r->keys[0];
    $impressions = $r->impressions;
    $clicks = $r->clicks;
    $ctr = $r->ctr;
    $position = $r->position;
    printf($Mask ,$page ,$impressions ,$clicks ,$ctr , $position );
    printf("|\n");
}
