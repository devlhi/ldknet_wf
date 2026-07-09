<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'nama',
        'nomor',
        'password',
        'balance',
        'level',
        'verify_account',
        'status_account',
    ];

    protected $hidden = [
        'password',
    ];

    public function isVerified(): bool
    {
        return (int) $this->verify_account === 1;
    }

    public function isActive(): bool
    {
        return $this->status_account === 'Active';
    }
}
