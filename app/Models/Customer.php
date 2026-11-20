<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'company',
        'tier',
        'country',
        'balance',
        'is_active',
        'last_active_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the jobs belonging to this customer.
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
