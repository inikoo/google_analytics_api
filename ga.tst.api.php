<?php
/**
 * Created by PhpStorm.
 * User: sasi
 * Date: 07/04/2019
 * Time: 10:05
 */
require_once __DIR__ . '/vendor/autoload.php';

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


if (strpos($au_date, ':') !== false){
    $au_date_arr = preg_split ("/\:/", $au_date);
    $au_date_from = $au_date_arr[0];
    $au_date_to = $au_date_arr[1];
} else {
    $au_date_from = $au_date;
    $au_date_to = $au_date;
}

if ($au_display  == 't'){
    $analytics = initializeAnalytics();
    $response = getReport($analytics);
    printResults_t($response);
} elseif ($au_display == 'l'){
    $analytics = initializeAnalytics();
    $response = getReport($analytics);
    printResults($response);
} elseif ($au_display == 'd'){
    $analytics = initializeAnalytics();
    $response = getReport($analytics);
    printResults_d($response);
} else {
    echo "Please pass correct arguments for display.\n";
    exit;
}

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

    global $au_view, $au_date_from, $au_date_to, $au_metrics;

    $VIEW_ID = $au_view;

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
    $request->setPageToken('2000');
    $request->setMetrics(array($pageviews,$pageValue,$users,$sessions));

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests(array($request));
    return $analytics->reports->batchGet( $body );
}

function printResults($reports) {
    for ( $reportIndex = 0; $reportIndex < count($reports); $reportIndex++ ) {
        $report = $reports[ $reportIndex ];
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows = $report->getData()->getRows();

        for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++ ) {
            $row = $rows[ $rowIndex ];
            $dimensions = $row->getDimensions();
            $metrics = $row->getMetrics();
            for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
            }

            for ($j = 0; $j < count($metrics); $j++) {
                $values = $metrics[$j]->getValues();
                for ($k = 0; $k < count($values); $k++) {
                    $entry = $metricHeaders[$k];
                    print($entry->getName() . ": " . $values[$k] . "\n");
                }
            }
        }
    }
}

function printResults_t($reports) {
    for ( $reportIndex = 0; $reportIndex < count($reports); $reportIndex++ ) {
        $report = $reports[ $reportIndex ];
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows = $report->getData()->getRows();
        $dimensionMask = "|%-90.90s ";
        $metricMask = "|%-30s ";
        /*printf($mask, 'Num', 'Title');*/

        for ($l = 0; $l < count($dimensionHeaders) ; $l++) {
            printf($dimensionMask, $dimensionHeaders[$l]);
        }
        for ($k = 0; $k < count($metricHeaders); $k++) {
            $entry = $metricHeaders[$k];
            printf($metricMask, $entry->getName());
        }
        printf("|\n");
        for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++ ) {
            $row = $rows[ $rowIndex ];
            $dimensions = $row->getDimensions();
            $metrics = $row->getMetrics();
            for ($i = 0; $i < count($dimensions); $i++) {
                printf($dimensionMask, $dimensions[$i]);
            }

            for ($j = 0; $j < count($metrics); $j++) {
                $values = $metrics[$j]->getValues();
                for ($k = 0; $k < count($values); $k++) {
                    printf($metricMask, $values[$k]);
                }
            }
            printf("|\n");
        }
    }
}
function printResults_d($reports) {
    /*        $rows = $reports[0]->getData()->getRows();
            foreach ($rows as $r) {
                $dimensions = $r->getDimensions();
                $metrics = $r->getMetrics();
                $values = $metrics[0]->getValues();
                printf($dimensions[0]."  |".$values[0]."  | ". $values[1]."  | ".$values[2]."  | ".$values[3]."  | ");
                printf("\n");
        }*/

    /*var_dump($reports[0]->getNextPageToken()->getRows());*/
    $PageToken = $reports[0]->getData()->getRowCount();
    /*print_r($PageToken);*/
    var_dump($PageToken);

}
