<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id', 'description', 'quantity', 'unit_price',
        'amount', 'item_type',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Auto-calculate amount when quantity or unit_price changes
    public static function boot(): void
    {
        parent::boot();

        static::saving(function (InvoiceItem $item) {
            $item->amount = round($item->quantity * $item->unit_price, 2);
        });

        // Recalculate invoice totals after saving an item
        static::saved(function (InvoiceItem $item) {
            $item->invoice->recalculateTotals();
        });

        static::deleted(function (InvoiceItem $item) {
            $item->invoice->recalculateTotals();
        });
    }
}
