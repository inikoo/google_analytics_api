<?php

require_once __DIR__ . '/vendor/autoload.php';

$analytics = initializeAnalytics();
$response = getReport($analytics);
printResults($response);

function initializeAnalytics()
{

    // Use the developers console and download your service account
    // credentials in JSON format. Place them in this directory or
    // change the key file location if necessary.
    $KEY_FILE_LOCATION = __DIR__ . '/secret.json';

    // Create and configure a new client object.
    $client = new Google_Client();
    $client->setApplicationName("Aurora Analytics");
    $client->setAuthConfig($KEY_FILE_LOCATION);
    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
    $analytics = new Google_Service_AnalyticsReporting($client);

    return $analytics;
}

function getReport($analytics) {

    $VIEW_ID = "152602933";

    $dateRange = new Google_Service_AnalyticsReporting_DateRange();
    $dateRange->setStartDate("2019-03-27");
    $dateRange->setEndDate("2019-04-02");

    // Create the Metrics object.
    /*$sessions = new Google_Service_AnalyticsReporting_Metric();
    $sessions->setExpression("ga:sessions");
    $sessions->setAlias("sessions");*/
    $pageviews = new Google_Service_AnalyticsReporting_Metric();
    $pageviews->setExpression("ga:pageviews");
    $pageviews->setAlias("pageviews");
    $pageValue = new Google_Service_AnalyticsReporting_Metric();
    $pageValue->setExpression("ga:pageValue");
    $pageValue->setAlias("pageValue");

    $pagePath = new Google_Service_AnalyticsReporting_Dimension();
    $pagePath->setName("ga:pagePath");

    $request = new Google_Service_AnalyticsReporting_ReportRequest();
    $request->setViewId($VIEW_ID);
    $request->setDateRanges($dateRange);
    $request->setDimensions(array($pagePath));
    $request->setMetrics(array($pageValue,$pageviews));

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