<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DamageReport extends Model
{
    protected $fillable = ['report_number', 'reporter_id', 'asset_id', 'description', 'reported_at', 'status', 'verified_by', 'verification_note'];
    protected function casts(): array { return ['reported_at' => 'datetime']; }
    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function ticket() { return $this->hasOne(MaintenanceTicket::class); }
    public function getStatusLabelAttribute(): string { return $this->status; }
}
