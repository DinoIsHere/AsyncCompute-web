<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'gpu_model_id',
        'offering_id',
        'name',
        'status',
        'region',
        'gpu_count',
        'price_per_gpu_hour',
        'public_ip',
        'ssh_key_name',
        'launched_at',
        'terminated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_per_gpu_hour' => 'decimal:2',
        'launched_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    /**
     * Get the customer who owns this instance.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the GPU model of this instance.
     */
    public function gpuModel(): BelongsTo
    {
        return $this->belongsTo(GpuModel::class);
    }

    /**
     * Get the offering this instance was launched from.
     */
    public function offering(): BelongsTo
    {
        return $this->belongsTo(InstanceOffering::class);
    }
}
