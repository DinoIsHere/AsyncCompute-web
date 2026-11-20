<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGpuModelRequest;
use App\Http\Requests\Admin\UpdateGpuModelRequest;
use App\Models\GpuModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InstanceController extends Controller
{
    /**
     * List the GPU plans in the admin catalogue.
     */
    public function index(): Response
    {
        $plans = GpuModel::withCount('offerings')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Instances/Index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show the create form for a new GPU plan.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Instances/Create');
    }

    /**
     * Store a new GPU plan and its offerings.
     */
    public function store(StoreGpuModelRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $imagePath = $this->storeImage($request);

        DB::transaction(function () use ($data, $imagePath) {
            $plan = GpuModel::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'vram_gb' => $data['vram_gb'],
                'vcpus' => $data['vcpus'],
                'ram_gb' => $data['ram_gb'],
                'storage' => $data['storage'],
                'price_per_gpu_hour' => $data['price_per_gpu_hour'],
                'description' => $data['description'] ?? null,
                'image_path' => $imagePath,
                'is_active' => $data['is_active'],
            ]);

            $plan->offerings()->createMany(
                collect($data['offerings'])
                    ->values()
                    ->map(fn (array $offering, int $index) => [
                        'gpu_count' => $offering['gpu_count'],
                        'region' => $offering['region'],
                        'stock' => $offering['stock'],
                        'sort_order' => $index,
                    ])
                    ->all()
            );
        });

        return redirect('/admin/instances');
    }

    /**
     * Show the edit form for an existing GPU plan.
     */
    public function edit(GpuModel $gpuModel): Response
    {
        return Inertia::render('Admin/Instances/Edit', [
            'plan' => $gpuModel->load('offerings'),
        ]);
    }

    /**
     * Update a GPU plan and replace all of its offerings atomically.
     */
    public function update(UpdateGpuModelRequest $request, GpuModel $gpuModel): RedirectResponse
    {
        $data = $request->validated();

        // Keep the current image unless a new file was actually uploaded.
        $imagePath = $request->hasFile('image')
            ? $this->storeImage($request)
            : $gpuModel->image_path;

        DB::transaction(function () use ($data, $imagePath, $gpuModel) {
            $gpuModel->update([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'vram_gb' => $data['vram_gb'],
                'vcpus' => $data['vcpus'],
                'ram_gb' => $data['ram_gb'],
                'storage' => $data['storage'],
                'price_per_gpu_hour' => $data['price_per_gpu_hour'],
                'description' => $data['description'] ?? null,
                'image_path' => $imagePath,
                'is_active' => $data['is_active'],
            ]);

            // Re-sync offerings: remove every row, then recreate from the submitted rows.
            $gpuModel->offerings()->delete();

            $gpuModel->offerings()->createMany(
                collect($data['offerings'])
                    ->values()
                    ->map(fn (array $offering, int $index) => [
                        'gpu_count' => $offering['gpu_count'],
                        'region' => $offering['region'],
                        'stock' => $offering['stock'],
                        'sort_order' => $index,
                    ])
                    ->all()
            );
        });

        return redirect('/admin/instances');
    }

    /**
     * Delete a GPU plan. Offerings are removed by the FK cascade; uploaded
     * files are deliberately left on disk (no orphan cleanup) by design.
     */
    public function destroy(GpuModel $gpuModel): RedirectResponse
    {
        $gpuModel->delete();

        return redirect('/admin/instances');
    }

    /**
     * Store an uploaded plan image on the public disk and return its path.
     */
    protected function storeImage(FormRequest $request): ?string
    {
        $file = $request->file('image');

        if (! $file) {
            return null;
        }

        return Storage::disk('public')->putFile('gpu-models', $file);
    }
}
