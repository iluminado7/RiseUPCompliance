<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SucursalOnboarding extends Model
{
    protected $table = 'branches_onboarding';

    public $timestamps = false;

    protected $fillable = [
        'token_id',
        'company_onboarding_id',
        'name',
        'internal_code',
        'address',
        'is_headquarter',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_headquarter' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(EmpresaOnboarding::class, 'company_onboarding_id');
    }
}
