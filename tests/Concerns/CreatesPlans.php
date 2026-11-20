<?php

namespace Tests\Concerns;

use App\Models\GpuModel;
use Illuminate\Support\Str;

trait CreatesPlans
{
    /**
     * Create a GPU plan with a derived slug and, by default, one single-GPU offering.
     *
     * Offering overrides may be supplied via the "offering" key (a single
     * offering's attributes, a list of offerings, or false to skip) or via the
     * "offerings" key (a list of offering attribute arrays).
     */
    protected function createPlan(array $overrides = []): GpuModel
    {
        $hasOfferingOverride = array_key_exists('offering', $overrides)
            || array_key_exists('offerings', $overrides);
        $offeringOverrides = $overrides['offering'] ?? $overrides['offerings'] ?? null;
        unset($overrides['offering'], $overrides['offerings']);

        $attributes = array_merge([
            'name' => 'HGX H100',
            'vram_gb' => 80,
            'vcpus' => 104,
            'ram_gb' => 768,
            'storage' => '2x 3.84 TB NVMe',
            'price_per_gpu_hour' => 4.99,
            'description' => 'Test plan',
            'image_path' => 'images/gpu-models/h100.svg',
            'is_active' => true,
        ], $overrides);

        if (! array_key_exists('slug', $overrides)) {
            $attributes['slug'] = Str::slug($attributes['name']);
        }

        $plan = GpuModel::create($attributes);

        $offeringDefaults = [
            'gpu_count' => 1,
            'region' => 'us-east-1',
            'stock' => 10,
            'sort_order' => 0,
        ];

        if (! $hasOfferingOverride) {
            $plan->offerings()->create($offeringDefaults);
        } elseif (is_array($offeringOverrides)) {
            if ($this->isListOfOfferings($offeringOverrides)) {
                foreach ($offeringOverrides as $offering) {
                    $plan->offerings()->create(array_merge($offeringDefaults, $offering));
                }
            } else {
                $plan->offerings()->create(array_merge($offeringDefaults, $offeringOverrides));
            }
        }

        return $plan;
    }

    /**
     * Determine whether the given array is a list of offering arrays.
     */
    private function isListOfOfferings(array $offeringOverrides): bool
    {
        if ($offeringOverrides === []) {
            return true;
        }

        return array_keys($offeringOverrides) === range(0, count($offeringOverrides) - 1);
    }
}
