<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpuNode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hostname',
        'gpu_model_id',
        'region',
        'gpu_count',
        'status',
        'cpu_util',
        'mem_util',
        'gpu_util',
        'temp_c',
        'power_w',
        'last_heartbeat_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cpu_util' => 'decimal:1',
        'mem_util' => 'decimal:1',
        'gpu_util' => 'decimal:1',
        'temp_c' => 'decimal:1',
        'last_heartbeat_at' => 'datetime',
    ];

    /**
     * Get the GPU model installed in this node.
     */
    public function gpuModel(): BelongsTo
    {
        return $this->belongsTo(GpuModel::class);
    }
}
