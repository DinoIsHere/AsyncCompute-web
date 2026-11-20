<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\GpuModel;
use App\Models\Instance;
use App\Models\InstanceOffering;
use App\Http\Requests\LaunchInstanceRequest;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LaunchController extends Controller
{
    // Demo customer used for the public launch flow (school project).
    private const DEMO_CUSTOMER_EMAIL = 'demo@asyncindo.id';

    /**
     * Show the launch-instance wizard.
     */
    public function create(): Response
    {
        $plans = GpuModel::with('offerings')
            ->where('is_active', true)
            ->orderBy('price_per_gpu_hour')
            ->get()
            ->map(fn (GpuModel $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'vram_gb' => $plan->vram_gb,
                'vcpus' => $plan->vcpus,
                'ram_gb' => $plan->ram_gb,
                'storage' => $plan->storage,
                'price_per_gpu_hour' => (float) $plan->price_per_gpu_hour,
                'description' => $plan->description,
                'offerings' => $plan->offerings
                    ->map(fn (InstanceOffering $offering) => [
                        'id' => $offering->id,
                        'gpu_count' => $offering->gpu_count,
                        'region' => $offering->region,
                        'stock' => $offering->stock,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        $myInstances = Instance::with('gpuModel:id,name')
            ->where('customer_id', $this->demoCustomer()->id)
            ->where('status', '!=', 'terminated')
            ->latest()
            ->get()
            ->map(fn (Instance $instance) => [
                'id' => $instance->id,
                'name' => $instance->name,
                'model' => $instance->gpuModel->name,
                'region' => $instance->region,
                'gpu_count' => $instance->gpu_count,
                'status' => $instance->status,
                'public_ip' => $instance->public_ip,
                'launched_at' => $instance->launched_at?->diffForHumans(),
            ])
            ->all();

        return Inertia::render('Public/LaunchInstance', [
            'plans' => $plans,
            'myInstances' => $myInstances,
        ]);
    }

    /**
     * Provision a new instance (decrement stock atomically).
     */
    public function store(LaunchInstanceRequest $request)
    {
        $validated = $request->validated();

        $instance = DB::transaction(function () use ($validated) {
            // Lock the offering row to avoid overselling on concurrent launches
            $offering = InstanceOffering::where('stock', '>', 0)
                ->lockForUpdate()
                ->findOrFail($validated['offering_id']);

            $offering->decrement('stock');

            $gpuModel = GpuModel::findOrFail($offering->gpu_model_id);

            return Instance::create([
                'customer_id' => $this->demoCustomer()->id,
                'gpu_model_id' => $gpuModel->id,
                'offering_id' => $offering->id,
                'name' => $validated['name'],
                'status' => 'provisioning',
                'region' => $offering->region,
                'gpu_count' => $offering->gpu_count,
                'price_per_gpu_hour' => $gpuModel->price_per_gpu_hour,
                'public_ip' => long2ip(random_int(ip2long('10.16.0.1'), ip2long('10.31.255.254'))),
                'ssh_key_name' => $validated['ssh_key_name'] ?? null,
                'launched_at' => now(),
            ]);
        });

        return redirect()
            ->route('launch.create')
            ->with('success', "Instance {$instance->name} is provisioning.");
    }

    /**
     * Stop a running instance.
     */
    public function stop(Instance $instance)
    {
        abort_if($instance->status !== 'running', 422, 'Instance is not running.');

        $instance->update(['status' => 'stopped']);

        return redirect()->route('launch.create')->with('success', "Instance {$instance->name} stopped.");
    }

    /**
     * Terminate an instance (frees the stock back).
     */
    public function terminate(Instance $instance)
    {
        if ($instance->status !== 'terminated') {
            $instance->update([
                'status' => 'terminated',
                'terminated_at' => now(),
            ]);

            // Return capacity to the pool
            InstanceOffering::where('id', $instance->offering_id)->increment('stock');
        }

        return redirect()->route('launch.create')->with('success', "Instance {$instance->name} terminated.");
    }

    /**
     * Resolve (or create) the demo customer for this session-less flow.
     */
    private function demoCustomer(): Customer
    {
        return Customer::firstOrCreate(
            ['email' => self::DEMO_CUSTOMER_EMAIL],
            [
                'name' => 'Demo Customer',
                'company' => 'Asyncindo Demo',
                'tier' => 'starter',
                'country' => 'Indonesia',
                'balance' => 250.00,
            ]
        );
    }
}
