<?php
/**
 * Created by PhpStorm.
 * User: sasi
 * Date: 04/04/2019
 * Time: 7:25
 */

require_once __DIR__ . '/vendor/autoload.php';

$KEY_FILE_LOCATION = __DIR__ . '/secret.json';

$client = new Google_Client();
$client->setApplicationName("Aurora Search Console");
$client->setAuthConfig($KEY_FILE_LOCATION);
$client->setScopes(['https://www.googleapis.com/auth/webmasters.readonly']);


$webmastersService = new Google_Service_Webmasters($client);

$query = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
$query->setDimensions(array('page'));
$query->setStartDate('2019-03-01');
$query->setEndDate('2019-04-02');

$filter = new Google_Service_Webmasters_ApiDimensionFilter();
/*$filter->setDimension('query');
$filter->setOperator('equals');
$filter->setExpression('w');*/
$filter->setDimension("device");
$filter->setExpression("MOBILE");

$filterGroup = new Google_Service_Webmasters_ApiDimensionFilterGroup();
$filterGroup->setFilters(array($filter));
$query->setDimensionFilterGroups(array($filterGroup));

$response = $webmastersService->searchanalytics->query('https://www.awgifts.eu/', $query);
$Mask = "|%-50s |%-30s |%-30s |%-30s |%-30s";
printf($Mask ,'keys', 'impressions', 'clicks', 'ctr' ,'position');
printf("|\n");
foreach ($response->rows as $r) {
    printf($Mask ,$r->keys[0] ,$r->impressions ,$r->clicks ,$r->ctr , $r->position );
    printf("|\n");
}