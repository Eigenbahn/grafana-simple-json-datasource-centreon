<?php

namespace App\Services\GrafanaSimpleHttpJsonBackend;

use App\Services\CentreonRestApi\CentreonInternalRestApiService;
use DateTime;
use \DB;
use Illuminate\Support\Facades\Cache;

use App\Services\CentreonModel\MetricsService;



class GrafanaHttpJsonMetricsService {

    const inventory_cache_key = 'grafana_service_metrics_inventory';

    // -------------------------------------------------------------------
    // MAIN

    public function getMetricsForServices ($from, $to, $requestedServices) {
        $restApiSvc = new CentreonInternalRestApiService(config('app.centreon_internal_rest_api_url'),
            config('app.centreon_rest_api_username'), config('app.centreon_rest_api_password'));

        $output = [];

        $from = str_replace('T', ' ', $from);
        $from = str_replace('Z', ' UTC', $from);
        $fromDt = DateTime::createFromFormat("Y-m-d H:i:s.u P", $from);

        $to = str_replace('T', ' ', $to);
        $to = str_replace('Z', ' UTC', $to);
        $toDt = DateTime::createFromFormat("Y-m-d H:i:s.u P", $to);

        foreach ($requestedServices as $target) {
            $serviceIndexDataId = @$target['target'];
			if (! isset($serviceIndexDataId))
				# NB: first query sent w/ no specified target
				continue;
            $rawCentreonMetrics = $restApiSvc->metricsDataByService($serviceIndexDataId, $fromDt->getTimestamp(), $toDt->getTimestamp());
            $additionalOutput = $this->convertFormatMetricsListFromCentreonRestApiToGrafanaJson($rawCentreonMetrics);
            $output = array_merge($output, $additionalOutput);
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
}