<?php

require_once __DIR__ . '/vendor/autoload.php';

$analytics = initializeAnalytics();
$response = getReport($analytics);
printResults($response);

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