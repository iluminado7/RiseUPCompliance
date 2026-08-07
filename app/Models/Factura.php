<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Factura mensual de una empresa. afip_cae es el código de autorización. */
class Factura extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'invoices';

    protected $fillable = [
        'company_id',
        'billing_month',
        'billing_year',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'currency_code',
        'afip_cae',
        'afip_cae_expiry',
        'invoice_number',
        'payment_status',
        'concept',
        'issue_date',
        'due_date',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'afip_cae_expiry' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'payment_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeImpagas(Builder $query): Builder
    {
        return $query->whereIn('payment_status', ['pending', 'overdue']);
    }
}
