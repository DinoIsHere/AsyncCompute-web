<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\Customer;
use App\Models\DailyRevenue;
use App\Models\GpuModel;
use App\Models\GpuNode;
use App\Models\InstanceOffering;
use App\Models\Job;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard: hardware, clusters, revenue, customers.
     */
    public function index(): Response
    {
        // ------------------------------------------------------------------
        // Headline stats
        // ------------------------------------------------------------------
        $revenueToday = (float) DailyRevenue::whereDate('date', Carbon::today())->value('revenue');
        $revenueYesterday = (float) DailyRevenue::whereDate('date', Carbon::yesterday())->value('revenue');
        $revenue30d = (float) DailyRevenue::whereDate('date', '>=', Carbon::today()->subDays(29))->sum('revenue');
        $revenuePrev30d = (float) DailyRevenue::whereDate('date', '>=', Carbon::today()->subDays(59))
            ->whereDate('date', '<', Carbon::today()->subDays(29))
            ->sum('revenue');

        $totalGpus = (int) GpuNode::whereIn('status', ['online', 'draining'])->sum('gpu_count');
        $gpusBusy = (int) round(GpuNode::whereIn('status', ['online', 'draining'])->sum('gpu_util') / 100);

        $stats = [
            [
                'label' => 'Revenue (30 days)',
                'value' => '$' . number_format($revenue30d, 0),
                'delta' => $revenuePrev30d > 0
                    ? round(($revenue30d - $revenuePrev30d) / $revenuePrev30d * 100, 1)
                    : null,
                'deltaLabel' => 'vs previous 30d',
            ],
            [
                'label' => 'Revenue today',
                'value' => '$' . number_format($revenueToday, 2),
                'delta' => $revenueYesterday > 0
                    ? round(($revenueToday - $revenueYesterday) / $revenueYesterday * 100, 1)
                    : null,
                'deltaLabel' => 'vs yesterday',
            ],
            [
                'label' => 'Fleet GPU utilization',
                'value' => $totalGpus > 0 ? round($gpusBusy / $totalGpus * 100) . '%' : '0%',
                'delta' => null,
                'deltaLabel' => sprintf('%d of %d GPUs busy', $gpusBusy, $totalGpus),
            ],
            [
                'label' => 'Active customers',
                'value' => Customer::where('is_active', true)->count(),
                'delta' => null,
                'deltaLabel' => sprintf('%s outstanding balance', '$' . number_format((float) Customer::where('is_active', true)->sum('balance'), 0)),
            ],
        ];

        // ------------------------------------------------------------------
        // Revenue chart (last 30 days) + job-type distribution
        // ------------------------------------------------------------------
        $revenueSeries = DailyRevenue::whereDate('date', '>=', Carbon::today()->subDays(29))
            ->orderBy('date')
            ->get(['date', 'revenue', 'gpu_hours'])
            ->map(fn (DailyRevenue $row) => [
                'date' => $row->date->format('M j'),
                'revenue' => (float) $row->revenue,
                'gpu_hours' => (int) $row->gpu_hours,
            ])
            ->values()
            ->all();

        $revenueMax = max(1.0, max(array_column($revenueSeries, 'revenue')));

        $jobsByType = Job::selectRaw('type, count(*) as aggregate, coalesce(sum(cost), 0) as cost')
            ->groupBy('type')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'count' => (int) $row->aggregate,
                'cost' => (float) $row->cost,
            ])
            ->values()
            ->all();

        $regionDemand = GpuNode::selectRaw('region, count(*) as nodes, coalesce(avg(gpu_util), 0) as avg_util')
            ->groupBy('region')
            ->orderBy('region')
            ->get()
            ->map(fn ($row) => [
                'region' => $row->region,
                'nodes' => (int) $row->nodes,
                'avg_util' => round((float) $row->avg_util, 1),
            ])
            ->values()
            ->all();

        // ------------------------------------------------------------------
        // Hardware fleet
        // ------------------------------------------------------------------
        $nodes = GpuNode::with('gpuModel:id,name')
            ->orderBy('hostname')
            ->limit(12)
            ->get()
            ->map(fn (GpuNode $node) => [
                'id' => $node->id,
                'hostname' => $node->hostname,
                'model' => $node->gpuModel->name,
                'region' => $node->region,
                'gpu_count' => $node->gpu_count,
                'status' => $node->status,
                'cpu_util' => (float) $node->cpu_util,
                'mem_util' => (float) $node->mem_util,
                'gpu_util' => (float) $node->gpu_util,
                'temp_c' => (float) $node->temp_c,
                'power_w' => (int) $node->power_w,
            ])
            ->all();

        $fleetSummary = [
            'online' => GpuNode::where('status', 'online')->count(),
            'draining' => GpuNode::where('status', 'draining')->count(),
            'maintenance' => GpuNode::where('status', 'maintenance')->count(),
            'offline' => GpuNode::where('status', 'offline')->count(),
            'totalPowerKw' => round((float) GpuNode::whereIn('status', ['online', 'draining'])->sum('power_w') / 1000, 1),
        ];

        // ------------------------------------------------------------------
        // Clusters
        // ------------------------------------------------------------------
        $clusters = Cluster::with('gpuModel:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Cluster $cluster) => [
                'id' => $cluster->id,
                'name' => $cluster->name,
                'region' => $cluster->region,
                'model' => $cluster->gpuModel->name,
                'node_count' => $cluster->node_count,
                'gpu_total' => $cluster->gpu_total,
                'status' => $cluster->status,
                'utilization' => (float) $cluster->utilization,
            ])
            ->all();

        // ------------------------------------------------------------------
        // Recent jobs
        // ------------------------------------------------------------------
        $recentJobs = Job::with(['customer:id,company,name', 'gpuModel:id,name'])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Job $job) => [
                'id' => $job->id,
                'name' => $job->name,
                'customer' => $job->customer->company ?? $job->customer->name,
                'model' => $job->gpuModel->name,
                'type' => $job->type,
                'status' => $job->status,
                'gpu_count' => $job->gpu_count,
                'cost' => (float) $job->cost,
            ])
            ->all();

        // ------------------------------------------------------------------
        // Customers
        // ------------------------------------------------------------------
        $topCustomers = Customer::withSum('jobs as spend', 'cost')
            ->orderByDesc('spend')
            ->limit(6)
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'company' => $customer->company ?? $customer->name,
                'tier' => $customer->tier,
                'country' => $customer->country,
                'balance' => (float) $customer->balance,
                'spend' => (float) $customer->spend,
            ])
            ->all();

        $customersByTier = Customer::selectRaw('tier, count(*) as aggregate')
            ->groupBy('tier')
            ->pluck('aggregate', 'tier')
            ->all();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'revenueSeries' => $revenueSeries,
            'revenueMax' => $revenueMax,
            'jobsByType' => $jobsByType,
            'regionDemand' => $regionDemand,
            'nodes' => $nodes,
            'fleetSummary' => $fleetSummary,
            'clusters' => $clusters,
            'recentJobs' => $recentJobs,
            'topCustomers' => $topCustomers,
            'customersByTier' => $customersByTier,
            'plansCount' => GpuModel::count(),
            'offeringsCount' => InstanceOffering::count(),
            'inactiveCount' => GpuModel::where('is_active', false)->count(),
        ]);
    }
}
