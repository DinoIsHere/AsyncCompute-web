<?php

namespace Database\Seeders;

use App\Models\Cluster;
use App\Models\Customer;
use App\Models\DailyRevenue;
use App\Models\GpuModel;
use App\Models\GpuNode;
use App\Models\Job;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DashboardSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo customers, nodes, clusters, jobs, and revenue history.
     *
     * @return void
     */
    public function run()
    {
        // Idempotent: clear previous demo data before re-seeding
        foreach ([DailyRevenue::class, Job::class, Cluster::class, GpuNode::class, Customer::class] as $model) {
            $model::query()->delete();
        }

        $models = GpuModel::all()->keyBy('name');

        // ------------------------------------------------------------------
        // Customers
        // ------------------------------------------------------------------
        $customers = [
            ['name' => 'Rizky Hidayat', 'email' => 'rizky@nusanlp.id', 'company' => 'NusaNLP', 'tier' => 'growth', 'country' => 'Indonesia', 'balance' => 1840.50, 'last_active_at' => Carbon::now()->subMinutes(4)],
            ['name' => 'Amelia Chen', 'email' => 'amelia@visionlabs.sg', 'company' => 'VisionLabs Pte Ltd', 'tier' => 'enterprise', 'country' => 'Singapore', 'balance' => 9420.00, 'last_active_at' => Carbon::now()->subMinutes(22)],
            ['name' => 'Bagus Setiawan', 'email' => 'bagus@katalog.co.id', 'company' => 'Katalog Commerce', 'tier' => 'starter', 'country' => 'Indonesia', 'balance' => 122.75, 'last_active_at' => Carbon::now()->subHours(3)],
            ['name' => 'Nguyen Minh', 'email' => 'minh@saigontech.vn', 'company' => 'SaigonTech AI', 'tier' => 'growth', 'country' => 'Vietnam', 'balance' => 3310.20, 'last_active_at' => Carbon::now()->subHours(1)],
            ['name' => 'Putri Lestari', 'email' => 'putri@lab.ai.ui.ac.id', 'company' => 'UI AI Lab', 'tier' => 'free', 'country' => 'Indonesia', 'balance' => 0.00, 'last_active_at' => Carbon::now()->subDays(2)],
            ['name' => 'Daniel Santos', 'email' => 'daniel@medscan.my', 'company' => 'MedScan Sdn Bhd', 'tier' => 'enterprise', 'country' => 'Malaysia', 'balance' => 12875.40, 'last_active_at' => Carbon::now()->subMinutes(51)],
            ['name' => 'Fitri Handayani', 'email' => 'fitri@agrinusa.id', 'company' => 'AgriNusa', 'tier' => 'starter', 'country' => 'Indonesia', 'balance' => 45.10, 'last_active_at' => Carbon::now()->subDays(6)],
            ['name' => 'Joshua Tan', 'email' => 'joshua@quantworks.io', 'company' => 'QuantWorks', 'tier' => 'growth', 'country' => 'Singapore', 'balance' => 5604.90, 'last_active_at' => Carbon::now()->subMinutes(9)],
            ['name' => 'Sari Wulandari', 'email' => 'sari@batakuliah.id', 'company' => 'Batakuliah EdTech', 'tier' => 'starter', 'country' => 'Indonesia', 'balance' => 210.60, 'last_active_at' => Carbon::now()->subHours(7)],
            ['name' => 'Kevin Wijaya', 'email' => 'kevin@floatcharge.id', 'company' => 'FloatCharge', 'tier' => 'growth', 'country' => 'Indonesia', 'balance' => 2890.00, 'last_active_at' => Carbon::now()->subHours(12)],
        ];

        $customerModels = [];
        foreach ($customers as $customer) {
            $customerModels[] = Customer::create($customer + ['is_active' => true]);
        }

        // ------------------------------------------------------------------
        // GPU nodes (hardware fleet)
        // ------------------------------------------------------------------
        $nodeSpecs = [
            ['model' => 'HGX B200', 'region' => 'ap-south-1', 'count' => 5],
            ['model' => 'HGX H100', 'region' => 'ap-south-1', 'count' => 8],
            ['model' => 'HGX H100', 'region' => 'us-east-1', 'count' => 6],
            ['model' => 'HGX A100 80GB', 'region' => 'ap-south-1', 'count' => 7],
            ['model' => 'HGX A100 40GB', 'region' => 'ap-south-1', 'count' => 9],
            ['model' => 'GH200', 'region' => 'us-east-1', 'count' => 4],
            ['model' => 'Tesla V100', 'region' => 'ap-south-1', 'count' => 6],
        ];

        $nodeIndex = 1;
        foreach ($nodeSpecs as $spec) {
            $model = $models[$spec['model']];

            for ($i = 1; $i <= $spec['count']; $i++) {
                // Mostly online with a few interesting exceptions
                $roll = random_int(1, 100);
                $status = match (true) {
                    $roll <= 82 => 'online',
                    $roll <= 90 => 'draining',
                    $roll <= 96 => 'maintenance',
                    default => 'offline',
                };

                $active = $status === 'online';

                GpuNode::create([
                    'hostname' => sprintf('gpu-%s-%02d', strtolower(str_replace(['HGX ', 'HGX', 'Tesla ', ' '], ['', '', '', '-'], $spec['model'])), $nodeIndex),
                    'gpu_model_id' => $model->id,
                    'region' => $spec['region'],
                    'gpu_count' => [1, 2, 4, 8][$i % 4],
                    'status' => $status,
                    'cpu_util' => $active ? random_int(15, 90) + random_int(0, 9) / 10 : 0,
                    'mem_util' => $active ? random_int(20, 88) + random_int(0, 9) / 10 : 0,
                    'gpu_util' => $active ? random_int(35, 99) + random_int(0, 9) / 10 : 0,
                    'temp_c' => $active ? random_int(52, 78) + random_int(0, 9) / 10 : random_int(28, 35),
                    'power_w' => $active ? random_int(900, 5800) : random_int(120, 300),
                    'last_heartbeat_at' => $status === 'offline'
                        ? Carbon::now()->subHours(random_int(2, 20))
                        : Carbon::now()->subSeconds(random_int(5, 55)),
                ]);

                $nodeIndex++;
            }
        }

        // ------------------------------------------------------------------
        // Clusters
        // ------------------------------------------------------------------
        $clusters = [
            ['name' => 'jakarta-prod-a', 'region' => 'ap-south-1', 'model' => 'HGX H100', 'node_count' => 8, 'gpu_total' => 64, 'status' => 'healthy', 'utilization' => 87.4],
            ['name' => 'jakarta-train-b', 'region' => 'ap-south-1', 'model' => 'HGX B200', 'node_count' => 5, 'gpu_total' => 40, 'status' => 'healthy', 'utilization' => 92.1],
            ['name' => 'jakarta-dev-c', 'region' => 'ap-south-1', 'model' => 'HGX A100 40GB', 'node_count' => 9, 'gpu_total' => 36, 'status' => 'degraded', 'utilization' => 41.8],
            ['name' => 'virginia-infer-1', 'region' => 'us-east-1', 'model' => 'HGX H100', 'node_count' => 6, 'gpu_total' => 48, 'status' => 'healthy', 'utilization' => 76.2],
            ['name' => 'virginia-grace-1', 'region' => 'us-east-1', 'model' => 'GH200', 'node_count' => 4, 'gpu_total' => 16, 'status' => 'healthy', 'utilization' => 63.5],
            ['name' => 'legacy-v100-pool', 'region' => 'ap-south-1', 'model' => 'Tesla V100', 'node_count' => 6, 'gpu_total' => 24, 'status' => 'critical', 'utilization' => 18.9],
        ];

        foreach ($clusters as $cluster) {
            Cluster::create([
                'name' => $cluster['name'],
                'region' => $cluster['region'],
                'gpu_model_id' => $models[$cluster['model']]->id,
                'node_count' => $cluster['node_count'],
                'gpu_total' => $cluster['gpu_total'],
                'status' => $cluster['status'],
                'utilization' => $cluster['utilization'],
            ]);
        }

        // ------------------------------------------------------------------
        // Jobs
        // ------------------------------------------------------------------
        $jobTemplates = [
            ['name' => 'llama3-id-sft', 'type' => 'fine-tuning', 'model' => 'HGX H100', 'gpus' => 8],
            ['name' => 'speech-bahasa-v3', 'type' => 'training', 'model' => 'HGX B200', 'gpus' => 8],
            ['name' => 'img-seg-prod', 'type' => 'inference', 'model' => 'Tesla V100', 'gpus' => 1],
            ['name' => 'receipt-ocr', 'type' => 'inference', 'model' => 'HGX A100 40GB', 'gpus' => 2],
            ['name' => 'vit-imagenet-bench', 'type' => 'benchmark', 'model' => 'GH200', 'gpus' => 4],
            ['name' => 'recommender-retrain', 'type' => 'training', 'model' => 'HGX A100 80GB', 'gpus' => 4],
            ['name' => 'asr-distill', 'type' => 'fine-tuning', 'model' => 'HGX H100', 'gpus' => 2],
            ['name' => 'diffusion-id', 'type' => 'training', 'model' => 'HGX B200', 'gpus' => 4],
        ];

        $statuses = ['running', 'running', 'running', 'queued', 'completed', 'completed', 'failed'];
        $jobDate = Carbon::now();

        foreach (range(1, 24) as $i) {
            $template = $jobTemplates[$i % count($jobTemplates)];
            $status = $statuses[$i % count($statuses)];
            $customer = $customerModels[array_rand($customerModels)];
            $startedAt = $jobDate->copy()->subHours(random_int(1, 72));

            Job::create([
                'customer_id' => $customer->id,
                'gpu_model_id' => $models[$template['model']]->id,
                'name' => $template['name'] . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'type' => $template['type'],
                'status' => $status,
                'gpu_count' => $template['gpus'],
                'cost' => round($models[$template['model']]->price_per_gpu_hour * $template['gpus'] * random_int(2, 30), 2),
                'started_at' => $status === 'queued' ? null : $startedAt,
                'ended_at' => in_array($status, ['completed', 'failed']) ? $startedAt->copy()->addHours(random_int(1, 20)) : null,
                'created_at' => $startedAt,
            ]);
        }

        // ------------------------------------------------------------------
        // Daily revenue (last 30 days, gently trending upward)
        // ------------------------------------------------------------------
        foreach (range(29, 0) as $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            $progress = (29 - $daysAgo) / 29; // 0 -> 1
            $base = 1800 + $progress * 2600;
            $noise = random_int(-350, 550);
            $weekend = $date->isWeekend() ? -420 : 0;
            $revenue = max(900, $base + $noise + $weekend);
            $avgPrice = 3.2;

            DailyRevenue::create([
                'date' => $date,
                'revenue' => round($revenue, 2),
                'gpu_hours' => (int) round($revenue / $avgPrice),
            ]);
        }
    }
}
