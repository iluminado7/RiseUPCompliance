<?php

namespace App\Models\Onboarding;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Invitación de alta de empresa. Vence a las 48 horas.
 *
 * El token viaja en la URL del formulario público, así que es el único
 * control de acceso de esa pantalla: tiene que generarse con
 * random_bytes y verificarse en tiempo constante.
 */
class TokenOnboarding extends Model
{
    protected $table = 'onboarding_tokens';

    public $timestamps = false;

    protected $fillable = ['token', 'created_by', 'expires_at', 'used_at', 'status'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function scopeUtilizables(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('expires_at', '>', now());
    }

    public function estaVencido(): bool
    {
        return $this->expires_at->isPast();
    }

    public function esUtilizable(): bool
    {
        return $this->status === 'pending' && ! $this->estaVencido();
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'created_by');
    }

    public function empresa(): HasOne
    {
        return $this->hasOne(EmpresaOnboarding::class, 'token_id');
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(SucursalOnboarding::class, 'token_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(UsuarioOnboarding::class, 'token_id');
    }

    public function datosFiscales(): HasOne
    {
        return $this->hasOne(DatosFiscalesOnboarding::class, 'token_id');
    }
}
