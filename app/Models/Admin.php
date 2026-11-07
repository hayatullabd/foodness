<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'admin';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['Super Admin', 'Super admin', 'super_admin']);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['Admin', 'admin']);
    }

    public function isManager(): bool
    {
        return $this->hasRole(['Manager', 'manager']);
    }

    public function isStaff(): bool
    {
        return $this->hasRole(['Staff', 'staff']);
    }

    public function isModerator(): bool
    {
        return $this->hasRole(['Moderator', 'moderator']);
    }

    public function canDeleteOrders(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        try {
            return $this->hasAnyPermission(['orders.delete', 'order-delete']);
        } catch (\Throwable) {
            return false;
        }
    }
}
