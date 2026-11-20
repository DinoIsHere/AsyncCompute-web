<?php

namespace App\Http\Controllers;

use App\Models\GpuModel;
use App\Models\InstanceOffering;
use Inertia\Inertia;
use Inertia\Response;

class InstanceController extends Controller
{
    /**
     * Show the public GPU instance catalogue.
     */
    public function index(): Response
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
                'price_per_gpu_hour' => $plan->price_per_gpu_hour,
                'description' => $plan->description,
                'image_url' => $plan->image_url,
                'is_active' => $plan->is_active,
                'offerings' => $plan->offerings
                    ->map(fn (InstanceOffering $offering) => [
                        'id' => $offering->id,
                        'gpu_count' => $offering->gpu_count,
                        'region' => $offering->region,
                        'stock' => $offering->stock,
                        'sort_order' => $offering->sort_order,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('Public/Instances', ['plans' => $plans]);
    }
}
