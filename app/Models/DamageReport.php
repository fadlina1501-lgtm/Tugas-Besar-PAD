<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    protected $fillable = [
        'report_number', 'reporter_id', 'asset_id', 'description',
        'reported_at', 'status', 'verified_by', 'verification_note',
    ];

    protected function casts(): array
    {
        return ['reported_at' => 'datetime'];
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function ticket()
    {
        return $this->hasOne(MaintenanceTicket::class);
    }

    /** Pengguna Fasilitas mengirimkan laporan kerusakan. */
    public function submit(): bool
    {
        $this->status = 'Submitted';
        $this->reported_at ??= now();

        return $this->save();
    }

    /** Sarpras memvalidasi laporan atau meminta pelapor melakukan revisi. */
    public function validasi(User $sarpras, bool $valid, ?string $note = null): bool
    {
        $this->update([
            'status' => $valid ? 'Verified' : 'Need Revision',
            'verified_by' => $sarpras->id,
            'verification_note' => $note,
        ]);

        $this->ticket()->first()?->update([
            'status' => $valid ? 'Open' : 'Pending Verification',
        ]);

        return true;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status;
    }
}
