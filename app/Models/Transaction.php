<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'gateway_id',
        'external_id',
        'status',
        'amount',
        'card_last_numbers',
        'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'transaction_products')
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }

    public function transactionProducts()
    {
        return $this->hasMany(TransactionProduct::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
