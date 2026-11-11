<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'login';

    protected $fillable = [
        'id',
        'idtap',
        'username',
        'password',
        'level',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function hasClusterAdminAccess(): bool
    {
        return $this->isSuperAdmin() || strtolower(trim((string) $this->username)) === 'admin_cluster';
    }

    public function isSuperAdmin(): bool
    {
        return in_array(strtolower(trim((string) $this->username)), ['admin_super', 'indihome_msp'], true);
    }
}
