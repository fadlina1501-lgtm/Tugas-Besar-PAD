<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['email_verified_at' => 'datetime', 'password' => 'hashed']; }

    public const ROLES = [
        'pengguna' => 'Pengguna Fasilitas',
        'sarpras' => 'Sarpras',
        'teknisi' => 'Teknisi',
        'manajemen' => 'Manajemen',
    ];

    public function roleLabel(): string { return self::ROLES[$this->role] ?? ucfirst($this->role); }
    public function damageReports() { return $this->hasMany(DamageReport::class, 'reporter_id'); }
    public function assignedWorkOrders() { return $this->hasMany(WorkOrder::class, 'technician_id'); }
}
