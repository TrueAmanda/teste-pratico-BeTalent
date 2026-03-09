<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'priority',
        'base_url',
        'auth_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auth_config' => 'array',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
