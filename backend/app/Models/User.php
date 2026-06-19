<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'phone', 'password', 'department_id', 'position_id', 'enabled', 'is_super_admin', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function position(): BelongsTo { return $this->belongsTo(Position::class); }

    public function permissionCodes(): array
    {
        if ($this->is_super_admin) return ['*'];
        return $this->roles()->where('roles.enabled', true)
            ->with('permissions:id,code')->get()
            ->flatMap->permissions->pluck('code')->unique()->values()->all();
    }

    public function hasPermission(string $code): bool
    {
        return $this->is_super_admin || in_array($code, $this->permissionCodes(), true);
    }

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
            'enabled' => 'boolean',
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
