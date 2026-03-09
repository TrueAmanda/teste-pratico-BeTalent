<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'amount',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function transactionProducts()
    {
        return $this->hasMany(TransactionProduct::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
