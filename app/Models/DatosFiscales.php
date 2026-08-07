<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Datos fiscales de la empresa (1:1).
 *
 * tax_id es el CUIT. Es la clave natural de deduplicación entre módulos
 * cuando se construya la plataforma unificada (§8.1): identifica a una
 * organización mejor que el nombre. Validar con la regla portada de
 * Business Partner, no reescribirla.
 *
 * No usa PerteneceAEmpresa: se llega siempre desde la empresa, y el
 * acceso a facturación es decisión de Policy, no de scope.
 */
class DatosFiscales extends Model
{
    protected $table = 'company_fiscal_data';

    protected $fillable = [
        'company_id',
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
        return $this->belongsTo(Empresa::class, 'company_id');
    }
}
