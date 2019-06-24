<?php

namespace App\Services\GrafanaSimpleHttpJsonBackend;

use DateTime;
use \DB;

use App\Services\CentreonModel\EventsService;


class GrafanaHttpJsonEventsService {

    // -------------------------------------------------------------------
    // MAIN

    public function getEventsForServices ($from, $to, $requestedServices) {
        $eventSvc = new EventsService();
        $output = [];

        $from = str_replace('T', ' ', $from);
        $from = str_replace('Z', ' UTC', $from);
        $fromDt = DateTime::createFromFormat("Y-m-d H:i:s.u P", $from);
        $from = $fromDt->getTimestamp();

        $to = str_replace('T', ' ', $to);
        $to = str_replace('Z', ' UTC', $to);
        $toDt = DateTime::createFromFormat("Y-m-d H:i:s.u P", $to);
        $to = $toDt->getTimestamp();

        $interval = date_diff($fromDt, $toDt);

        foreach ($requestedServices as $target) {
            $hostServiceId = @$target['target'];
			if (! isset($hostServiceId))
				# NB: first query sent w/ no specified target
				continue;

            list($hostId, $serviceId) = explode('_', $hostServiceId);
            $additionalOutput = $eventSvc->getEventsForServicesViaMysql($from, $to, $hostId, $serviceId);

        }

        return $output;
    }


    // -------------------------------------------------------------------
    // MARSHALLING

    protected function convertFormatMetricsListFromCentreonRestApiToGrafanaJson ($rawCentreonRestMetricsList) {
        $output = [];

        foreach ($rawCentreonRestMetricsList as $rawCentreonRestMetrics) {
            $additionalOutput = $this->convertFormatMetricsFromCentreonRestApiToGrafanaJson($rawCentreonRestMetrics);
            $output = array_merge($output, $additionalOutput);
        }
        return $output;
    }

    protected function convertFormatMetricsFromCentreonRestApiToGrafanaJson ($rawCentreonRestMetrics) {
        $output = [];

        $target = $rawCentreonRestMetrics['service_id'];
        $timestamps = $rawCentreonRestMetrics['times'];
        $numPoints = $rawCentreonRestMetrics['size'];
        foreach ($rawCentreonRestMetrics['data'] as $column) {
            $name = $column['label'];
            $unit = $column['unit'];
            $values = $column['data'];

            $datapoints = array_map(function ($timestamp, $value) {
                return [
                    0 => $value,
                    1 => $timestamp * 1000,
                ];
            }, $timestamps, $values);

            $output []= [
                'target' => $name,
                'datapoints' => $datapoints,
            ];
        }

        return $output;
    }

    protected function convertFormatMetricsFromMysqlToGrafanaJson ($rawMysqlMetrics) {

    }


}