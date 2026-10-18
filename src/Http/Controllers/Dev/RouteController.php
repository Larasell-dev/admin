<?php

namespace Larasell\Admin\Http\Controllers\Dev;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * Lists the dev-only routes. Only registered in the local environment.
 */
class RouteController
{
    public function index(): JsonResponse
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => str_starts_with((string) $route->getName(), 'larasell.admin.dev.'))
            ->map(fn (RoutingRoute $route) => [
                'name' => $route->getName(),
                'methods' => array_values(array_diff($route->methods(), ['HEAD'])),
                'uri' => $route->uri(),
            ])
            ->values();

        return response()->json(['routes' => $routes]);
    }
}
