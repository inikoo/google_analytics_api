<?php
/**
 * Created by PhpStorm.
 * User: sasi
 * Date: 04/04/2019
 * Time: 7:25
 */

require_once __DIR__ . '/vendor/autoload.php';


if ($argc < 2){
    echo "Please pass arguments for property, date and dimensions.\n";
    exit;
} elseif ($argc < 3){
    echo "Please pass arguments for date and dimensions.\n";
    exit;
} /*elseif ($argc < 4){
    echo "Please pass arguments for dimensions\n";
    exit;
} */

$property_arr = array(
    "ag" => "https://www.agnesandcat.org/",
    "po" => "https://www.aw-podarki.com/",
    "re" => "https://www.aw-regali.com/",
    "at" => "https://www.awgifts.at/",
    "cz" => "https://www.awgifts.cz/",
    "eu" => "https://www.awgifts.eu/",
    "fr" => "https://www.awgifts.fr/",
    "pl" => "https://www.awgifts.pl/",
    "sk" => "https://www.awgifts.sk/");





if (array_key_exists($argv[1],$property_arr)){
    $au_property = $property_arr[$argv[1]];
} elseif (in_array($argv[1],$property_arr)){
    $au_property = $argv[1];
} else {
    $au_property = $argv[1]. ': Given property is not available in our list';
    echo $au_property . "\n";
    exit;
}

$au_date = $argv[2];

if (strpos($au_date, ':') !== false){
    $au_date_arr = preg_split ("/\:/", $au_date);
    $au_date_from = $au_date_arr[0];
    $au_date_to = $au_date_arr[1];
} else {
    $au_date_from = $au_date;
    $au_date_to = $au_date;
}

$au_dimensions= $argv[3];

$KEY_FILE_LOCATION = __DIR__ . '/secret.json';

$client = new Google_Client();
$client->setApplicationName("Aurora Search Console");
$client->setAuthConfig($KEY_FILE_LOCATION);
$client->setScopes(['https://www.googleapis.com/auth/webmasters.readonly']);


$webmastersService = new Google_Service_Webmasters($client);

$query = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
$query->setDimensions(array($au_dimensions));
$query->setStartDate($au_date_from);
$query->setEndDate($au_date_to);

/*$filter = new Google_Service_Webmasters_ApiDimensionFilter();
$filter->setDimension('query');
$filter->setOperator('equals');
$filter->setExpression('w');
$filter->setDimension("device");
$filter->setExpression("MOBILE");

$filterGroup = new Google_Service_Webmasters_ApiDimensionFilterGroup();
$filterGroup->setFilters(array($filter));
$query->setDimensionFilterGroups(array($filterGroup));*/

$response = $webmastersService->searchanalytics->query($au_property, $query);
$Mask = "|%-50s |%-30s |%-30s |%-30s |%-30s";
printf($Mask ,'keys', 'impressions', 'clicks', 'ctr' ,'position');
printf("|\n");
foreach ($response->rows as $r) {
    printf($Mask ,$r->keys[0] ,$r->impressions ,$r->clicks ,$r->ctr , $r->position );
    printf("|\n");
}