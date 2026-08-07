<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Usuario cargado en el alta, antes de confirmar.
 *
 * OJO: acá `role` es un enum de strings ('admin_principal' | 'gestor'),
 * mientras que en la tabla real users se usa role_id. La confirmación de
 * alta tiene que traducir uno en otro.
 */
class UsuarioOnboarding extends Model
{
    protected $table = 'users_onboarding';

    public $timestamps = false;

    protected $fillable = [
        'token_id',
        'company_onboarding_id',
        'first_name',
        'last_name',
        'email',
        'password_hash',
        'role',
    ];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(EmpresaOnboarding::class, 'company_onboarding_id');
    }
}
