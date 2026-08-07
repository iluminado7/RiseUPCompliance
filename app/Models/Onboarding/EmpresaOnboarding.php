<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Datos de empresa cargados en el formulario de alta, antes de confirmar.
 *
 * DIVERGENCIA HEREDADA: los defaults de retención no coinciden con los de
 * `companies` (3660 días acá contra 365 allá). Un alta por onboarding
 * queda con diez años de retención y una creada a mano con uno. Es una
 * decisión de negocio pendiente, no un bug del port.
 */
class EmpresaOnboarding extends Model
{
    protected $table = 'companies_onboarding';

    public $timestamps = false;

    protected $fillable = [
        'token_id',
        'name',
        'email',
        'slug',
        'default_language',
        'timezone',
        'retention_complaints_days',
        'retention_files_days',
        'retention_logs_days',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(TokenOnboarding::class, 'token_id');
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(SucursalOnboarding::class, 'company_onboarding_id')
            ->orderBy('display_order');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(UsuarioOnboarding::class, 'company_onboarding_id');
    }

    public function datosFiscales(): HasOne
    {
        return $this->hasOne(DatosFiscalesOnboarding::class, 'company_onboarding_id');
    }
}
