<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cluster extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'region',
        'gpu_model_id',
        'node_count',
        'gpu_total',
        'status',
        'utilization',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'utilization' => 'decimal:1',
    ];

    /**
     * Get the GPU model deployed in this cluster.
     */
    public function gpuModel(): BelongsTo
    {
        return $this->belongsTo(GpuModel::class);
    }
}
