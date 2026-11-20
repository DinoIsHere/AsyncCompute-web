<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GpuModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'vram_gb',
        'vcpus',
        'ram_gb',
        'storage',
        'price_per_gpu_hour',
        'description',
        'image_path',
        'is_active',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['image_url'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the public URL for the plan's image.
     */
    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image_path;

        if (empty($path)) {
            return null;
        }

        if (Str::startsWith($path, 'gpu-models/')) {
            return Storage::url($path);
        }

        return asset($path);
    }

    /**
     * Get the instance offerings for the plan.
     */
    public function offerings(): HasMany
    {
        return $this->hasMany(InstanceOffering::class);
    }
}
