<?php


if ($argc < 2){
    echo "Please pass arguments for display, view, date and metrics.\n";
    exit;
} elseif ($argc < 3){
    echo "Please pass arguments for view, date and metrics.\n";
    exit;
} elseif ($argc < 4){
    echo "Please pass arguments for date and metrics.\n";
    exit;
} elseif ($argc < 5){
    echo "Please pass arguments for metrics.\n";
    exit;
}
$au_display = $argv[1];
$au_view = $argv[2];
$au_date = $argv[3];
$au_metrics= $argv[4];

if ($au_display  == 't'){
    include "table.php";
} elseif ($au_display == 'l'){
    include "list.php";
} else {
    echo "Please pass correct arguments for display.\n";
    exit;
}

if (strpos($au_date, ':') !== false){
    $au_date_arr = preg_split ("/\:/", $au_date);
    $au_date_from = $au_date_arr[0];
    $au_date_to = $au_date_arr[1];
} else {
    $au_date_from = $au_date;
    $au_date_to = $au_date;
}

include "api.php";

function getReport($analytics) {

    global $au_view, $au_date_from, $au_date_to, $au_metrics;

    $VIEW_ID = $au_view;

    $dateRange = new Google_Service_AnalyticsReporting_DateRange();
    $dateRange->setStartDate($au_date_from);
    $dateRange->setEndDate($au_date_to);

    /*$sessions = new Google_Service_AnalyticsReporting_Metric();
    $sessions->setExpression("ga:sessions");
    $sessions->setAlias("sessions");*/
    $pageviews = new Google_Service_AnalyticsReporting_Metric();
    $pageviews->setExpression("ga:".$au_metrics);
    $pageviews->setAlias($au_metrics);
    /*$pageValue = new Google_Service_AnalyticsReporting_Metric();
    $pageValue->setExpression("ga:pageValue");
    $pageValue->setAlias("pageValue");*/

    $pagePath = new Google_Service_AnalyticsReporting_Dimension();
    $pagePath->setName("ga:pagePath");


    $request = new Google_Service_AnalyticsReporting_ReportRequest();
    $request->setViewId($VIEW_ID);
    $request->setDateRanges($dateRange);
    $request->setDimensions(array($pagePath));
    $request->setMetrics(array($pageviews));

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests(array($request));
    return $analytics->reports->batchGet( $body );
}