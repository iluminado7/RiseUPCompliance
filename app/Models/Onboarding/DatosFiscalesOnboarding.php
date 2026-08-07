<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatosFiscalesOnboarding extends Model
{
    protected $table = 'company_fiscal_data_onboarding';

    protected $fillable = [
        'token_id',
        'company_onboarding_id',
        'tax_id',
        'legal_name',
        'vat_status',
        'fiscal_address',
        'billing_emails',
        'uses_global_price',
        'custom_amount',
        'billing_day',
        'preferred_payment',
    ];

    protected function casts(): array
    {
        return [
            'billing_emails' => 'array',
            'uses_global_price' => 'boolean',
            'custom_amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(EmpresaOnboarding::class, 'company_onboarding_id');
    }
}
