<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del panel de gestión.
 *
 * Dos tipos, según cuál vínculo esté poblado:
 *   - de empresa:    company_id != null, manager_id == null
 *   - de plataforma: manager_id != null, company_id == null
 *
 * El denunciante NO está acá y nunca debe estarlo.
 *
 * Este modelo NO usa PerteneceAEmpresa: si se le aplicara el scope, un
 * usuario no podría verse a sí mismo cuando company_id es null, y el
 * login del superadmin quedaría roto. El filtrado por empresa en los
 * listados de usuarios va en la Policy.
 */
class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    public $timestamps = true;

    protected $fillable = [
        'manager_id',
        'company_id',
        'role_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password_hash',
        'status',
        'preferred_language',
        'timezone',
    ];

    protected $hidden = [
        'password_hash',
        'two_factor_secret_enc',
        'recovery_code_hash',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'status' => EstadoUsuario::class,
            'two_factor_enabled' => 'boolean',
            'must_change_password' => 'boolean',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'last_failed_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'recovery_expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * La columna de contraseña se llama password_hash, no password.
     * Sin esto, el guard `web` no encuentra el hash.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // ── Relaciones ──────────────────────────────────────────────

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'role_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function managerPlataforma(): BelongsTo
    {
        return $this->belongsTo(ManagerPlataforma::class, 'manager_id');
    }

    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class, 'user_branches', 'user_id', 'branch_id');
    }

    public function denunciasAsignadas(): HasMany
    {
        return $this->hasMany(Denuncia::class, 'assigned_to_user_id');
    }

    // ── Rol y permisos ──────────────────────────────────────────

    public function nombreRol(): ?RolUsuario
    {
        return RolUsuario::tryFrom($this->rol?->name ?? '');
    }

    public function tieneRol(RolUsuario ...$roles): bool
    {
        $actual = $this->nombreRol();

        return $actual !== null && in_array($actual, $roles, true);
    }

    public function esSuperadmin(): bool
    {
        return $this->tieneRol(RolUsuario::Superadmin);
    }

    public function esManagerDePlataforma(): bool
    {
        return $this->manager_id !== null;
    }

    public function puedeOperar(): bool
    {
        return $this->status->puedeOperar() && ! $this->estaBloqueado();
    }

    /**
     * Bloqueo por intentos fallidos.
     *
     * Las columnas locked_until y failed_attempts existen en el schema
     * desde el principio pero el sistema actual nunca las escribió ni las
     * leyó: el único freno era el contador por IP. Se implementa en la
     * Etapa 3 junto al login.
     */
    public function estaBloqueado(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function nombreCompleto(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
