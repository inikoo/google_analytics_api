<?php
/**
 * Created by PhpStorm.
 * User: sasi
 * Date: 04/04/2019
 * Time: 12:33
 */

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