<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id', 'workspace_id', 'midtrans_order_id',
        'midtrans_transaction_id', 'payment_type', 'status',
        'amount', 'snap_token', 'snap_redirect_url',
        'midtrans_payload', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'midtrans_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    protected $hidden = [
        'snap_token',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
