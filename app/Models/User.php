<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasApiTokens<\Laravel\Sanctum\PersonalAccessToken> */
    use HasApiTokens;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_OPERADOR = 'operador';

    /**
     * El administrador puede corregir cualquier pedido, incluidos los
     * entregados (que para el resto del personal son inmutables).
     */
    public function esAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
