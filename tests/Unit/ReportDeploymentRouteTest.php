<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportDeploymentRouteTest extends TestCase
{
    public function test_pm_report_deployment_is_available_to_pm_and_tif(): void
    {
        $route = Route::getRoutes()->getByName('pm.report_deployment');

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('role:pm,tif', $route->gatherMiddleware());
    }

    public function test_stage_duration_report_routes_are_removed(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('pm.stage_duration_report'));
        $this->assertNull(Route::getRoutes()->getByName('admin.stage_duration_report'));
    }
}
