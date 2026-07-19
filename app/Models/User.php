<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'settings',
        'phone',
        'postal_code',
        'region',
        'city',
        'address',
        'zip', // для совместимости
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_enabled',
        'last_login_at',
        'last_login_ip',
        'country_code',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'settings' => 'array',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'last_login_at' => 'datetime',
        ];
    }

    public function getIsAdminAttribute($value): bool
    {
        return (bool) $value;
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Роли пользователя
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Прямые права пользователя (минуя роли)
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    /**
     * Проверка наличия роли у пользователя
     */
    public function hasRole(string $roleSlug): bool
    {
        if ($this->is_admin) {
            return true; // Админы имеют все роли
        }

        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Проверка наличия права у пользователя
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->is_admin) {
            return true; // Админы имеют все права
        }

        // Проверка прямых прав
        if ($this->permissions()->where('slug', $permissionSlug)->exists()) {
            return true;
        }

        // Проверка прав через роли
        return $this->roles()->whereHas('permissions', function ($query) use ($permissionSlug) {
            $query->where('slug', $permissionSlug);
        })->exists();
    }

    /**
     * Проверка наличия любого из прав
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверка наличия всех прав
     */
    public function hasAllPermissions(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if (!$this->hasPermission($slug)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Назначить роль пользователю
     */
    public function assignRole(string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->first();
        if ($role && !$this->hasRole($roleSlug)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Удалить роль у пользователя
     */
    public function removeRole(string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Логи активности пользователя
     */
    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Получить отформатированный номер телефона
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        return \App\Rules\RussianPhone::format($this->phone);
    }

    /**
     * Получить полный адрес
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->postal_code,
            $this->region,
            $this->city,
            $this->address,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * История входов пользователя
     */
    public function loginHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\LoginHistory::class);
    }

    /**
     * Проверка, включена ли 2FA
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && !empty($this->two_factor_secret);
    }

    /**
     * Нормализация телефона при сохранении
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($user) {
            if ($user->phone) {
                $user->phone = \App\Rules\RussianPhone::normalize($user->phone);
            }
        });
    }
}
