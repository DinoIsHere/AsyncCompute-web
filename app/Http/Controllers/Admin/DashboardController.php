<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpuModel;
use App\Models\InstanceOffering;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard with catalogue counts.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'plansCount' => GpuModel::count(),
            'offeringsCount' => InstanceOffering::count(),
            'inactiveCount' => GpuModel::where('is_active', false)->count(),
        ]);
    }
}
