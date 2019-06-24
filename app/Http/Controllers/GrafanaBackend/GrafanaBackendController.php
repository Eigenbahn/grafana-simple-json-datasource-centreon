<?php

namespace App\Http\Controllers\GrafanaBackend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Services\GrafanaSimpleHttpJsonBackend\GrafanaHttpJsonMetricsService;
use App\Services\CentreonModel\MetricsService;


class GrafanaBackendController extends Controller {

    public function testConnection () {
        return response()->make();
    }

    /**
     * NB: To improve performance as well as user experience, we could
     *     cache the whole list and implement fuzzy matching instead
     *     or regex.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search (Request $request) {
        $svc = new MetricsService();

        $regex = $request->input('target');

        $services = $svc->getServiceMetricsInventory($regex);

        return response()->json($services);
    }

    public function query (Request $request) {
        $svc = new GrafanaHttpJsonMetricsService();

        $fromString = $request->input('range.from');
        $toString = $request->input('range.to');
        $requestedServices = $request->input('targets');

        $output = $svc->getMetricsForServices($fromString, $toString, $requestedServices);

        return response()->json($output);
    }

    public function annotations () {
        $annotations = [];

        return response()->json($annotations);
    }

    public function reloadCache () {
        $svc = new MetricsService();
        $svc->reloadCacheServiceMetricsInventory();
        return response()->make();
    }
}