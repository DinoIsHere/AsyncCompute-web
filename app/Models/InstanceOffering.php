<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstanceOffering extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gpu_model_id',
        'gpu_count',
        'region',
        'stock',
        'sort_order',
    ];

    /**
     * Get the GPU model that owns the offering.
     */
    public function gpuModel(): BelongsTo
    {
        return $this->belongsTo(GpuModel::class);
    }
}
