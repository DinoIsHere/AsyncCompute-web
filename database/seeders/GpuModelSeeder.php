<?php

namespace Database\Seeders;

use App\Models\GpuModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GpuModelSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the GPU plans and their instance offerings.
     *
     * @return void
     */
    public function run()
    {
        $plans = [
            [
                'name' => 'HGX B200',
                'vram_gb' => 180,
                'vcpus' => 128,
                'ram_gb' => 1152,
                'storage' => '4x 3.84 TB NVMe',
                'price_per_gpu_hour' => 10.50,
                'description' => 'NVIDIA Blackwell HGX B200, the flagship for frontier model training.',
                'image_path' => 'images/gpu-models/b200.svg',
                'is_active' => true,
                'offerings' => [
                    ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 4, 'sort_order' => 0],
                    ['gpu_count' => 4, 'region' => 'eu-west-1', 'stock' => 2, 'sort_order' => 1],
                    ['gpu_count' => 8, 'region' => 'ap-south-1', 'stock' => 0, 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'HGX H100',
                'vram_gb' => 80,
                'vcpus' => 104,
                'ram_gb' => 768,
                'storage' => '2x 3.84 TB NVMe',
                'price_per_gpu_hour' => 4.99,
                'description' => 'NVIDIA Hopper HGX H100 with NVLink for large-scale AI workloads.',
                'image_path' => 'images/gpu-models/h100.svg',
                'is_active' => true,
                'offerings' => [
                    ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 12, 'sort_order' => 0],
                    ['gpu_count' => 2, 'region' => 'eu-central-1', 'stock' => 6, 'sort_order' => 1],
                    ['gpu_count' => 4, 'region' => 'ap-south-1', 'stock' => 3, 'sort_order' => 2],
                    ['gpu_count' => 8, 'region' => 'us-west-2', 'stock' => 0, 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'HGX A100 80GB',
                'vram_gb' => 80,
                'vcpus' => 96,
                'ram_gb' => 512,
                'storage' => '2x 3.84 TB NVMe',
                'price_per_gpu_hour' => 2.49,
                'description' => 'NVIDIA Ampere HGX A100 with 80GB HBM2e for memory-hungry models.',
                'image_path' => 'images/gpu-models/a100-80.svg',
                'is_active' => true,
                'offerings' => [
                    ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 8, 'sort_order' => 0],
                    ['gpu_count' => 4, 'region' => 'eu-west-1', 'stock' => 2, 'sort_order' => 1],
                ],
            ],
            [
                'name' => 'HGX A100 40GB',
                'vram_gb' => 40,
                'vcpus' => 96,
                'ram_gb' => 512,
                'storage' => '2x 1.92 TB NVMe',
                'price_per_gpu_hour' => 1.89,
                'description' => 'NVIDIA Ampere HGX A100 with 40GB HBM2e for versatile AI computing.',
                'image_path' => 'images/gpu-models/a100-40.svg',
                'is_active' => true,
                'offerings' => [
                    ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 20, 'sort_order' => 0],
                    ['gpu_count' => 2, 'region' => 'eu-west-1', 'stock' => 5, 'sort_order' => 1],
                    ['gpu_count' => 4, 'region' => 'ap-south-1', 'stock' => 0, 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'GH200',
                'vram_gb' => 96,
                'vcpus' => 72,
                'ram_gb' => 480,
                'storage' => '2x 3.84 TB NVMe',
                'price_per_gpu_hour' => 3.75,
                'description' => 'NVIDIA Grace Hopper Superchip combining CPU and GPU in one fabric.',
                'image_path' => 'images/gpu-models/gh200.svg',
                'is_active' => true,
                'offerings' => [
                    ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 7, 'sort_order' => 0],
                    ['gpu_count' => 4, 'region' => 'eu-central-1', 'stock' => 1, 'sort_order' => 1],
                ],
            ],
            [
                'name' => 'Tesla V100',
                'vram_gb' => 16,
                'vcpus' => 32,
                'ram_gb' => 256,
                'storage' => '1x 1.92 TB NVMe',
                'price_per_gpu_hour' => 0.99,
                'description' => 'NVIDIA Tesla V100, a cost-effective choice for legacy inference jobs.',
                'image_path' => 'images/gpu-models/v100.svg',
                'is_active' => true,
                'offerings' => [
                    ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 15, 'sort_order' => 0],
                    ['gpu_count' => 2, 'region' => 'eu-west-1', 'stock' => 9, 'sort_order' => 1],
                    ['gpu_count' => 4, 'region' => 'ap-south-1', 'stock' => 0, 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            $offerings = $plan['offerings'];
            unset($plan['offerings']);

            $plan['slug'] = Str::slug($plan['name']);

            $gpuModel = GpuModel::create($plan);
            $gpuModel->offerings()->createMany($offerings);
        }
    }
}
