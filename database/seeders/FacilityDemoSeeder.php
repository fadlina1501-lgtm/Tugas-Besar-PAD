<?php
namespace Database\Seeders;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\DamageReport;
use App\Models\Location;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceTicket;
use App\Models\MaintenanceType;
use App\Models\SparePart;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class FacilityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $pengguna = User::updateOrCreate(['email' => 'user@campus.test'], ['name' => 'Alya Pengguna', 'password' => Hash::make('password123'), 'role' => 'pengguna']);
        $sarpras = User::updateOrCreate(['email' => 'sarpras@campus.test'], ['name' => 'Danu Sarpras', 'password' => Hash::make('password123'), 'role' => 'sarpras']);
        $teknisi = User::updateOrCreate(['email' => 'teknisi@campus.test'], ['name' => 'Budi Teknisi', 'password' => Hash::make('password123'), 'role' => 'teknisi']);
        User::updateOrCreate(['email' => 'manajemen@campus.test'], ['name' => 'Rina Manajemen', 'password' => Hash::make('password123'), 'role' => 'manajemen']);

        $ac = AssetCategory::firstOrCreate(['name' => 'Air Conditioner']);
        $lcd = AssetCategory::firstOrCreate(['name' => 'LCD Projector']);
        $lighting = AssetCategory::firstOrCreate(['name' => 'Lighting']);
        $b101 = Location::firstOrCreate(['building' => 'Gedung B Cacuk', 'floor' => 1, 'room' => 'B101']);
        $b102 = Location::firstOrCreate(['building' => 'Gedung B Cacuk', 'floor' => 1, 'room' => 'B102']);
        $c201 = Location::firstOrCreate(['building' => 'Gedung B Cacuk', 'floor' => 2, 'room' => 'C201']);
        $assets = [];
        $assets[] = Asset::updateOrCreate(['asset_code' => 'AC-B101-01'], ['name' => 'AC Ruang B101', 'condition' => 'Baik', 'category_id' => $ac->id, 'location_id' => $b101->id, 'purchase_date' => now()->subYears(2)->toDateString(), 'purchase_price' => 6500000]);
        $assets[] = Asset::updateOrCreate(['asset_code' => 'AC-B102-01'], ['name' => 'AC Ruang B102', 'condition' => 'Perlu Perbaikan', 'category_id' => $ac->id, 'location_id' => $b102->id, 'purchase_date' => now()->subYears(4)->toDateString(), 'purchase_price' => 7000000]);
        $assets[] = Asset::updateOrCreate(['asset_code' => 'LCD-C201-01'], ['name' => 'LCD Projector C201', 'condition' => 'Baik', 'category_id' => $lcd->id, 'location_id' => $c201->id, 'purchase_date' => now()->subYears(1)->toDateString(), 'purchase_price' => 5500000]);
        $assets[] = Asset::updateOrCreate(['asset_code' => 'LMP-B101-01'], ['name' => 'Lampu Ruang B101', 'condition' => 'Rusak', 'category_id' => $lighting->id, 'location_id' => $b101->id, 'purchase_date' => now()->subYears(3)->toDateString(), 'purchase_price' => 150000]);

        $corrective = MaintenanceType::firstOrCreate(['name' => 'Corrective Maintenance'], ['description' => 'Perbaikan setelah ditemukan kerusakan.']);
        MaintenanceType::firstOrCreate(['name' => 'Preventive Maintenance'], ['description' => 'Pemeliharaan terjadwal untuk mencegah kerusakan.']);
        MaintenanceType::firstOrCreate(['name' => 'Inspection'], ['description' => 'Pemeriksaan kondisi fasilitas.']);
        SparePart::updateOrCreate(['sku' => 'SP-AC-001'], ['name' => 'Filter AC', 'unit_price' => 150000, 'stock' => 0, 'unit' => 'unit']);
        SparePart::updateOrCreate(['sku' => 'SP-AC-002'], ['name' => 'Kapasitor AC', 'unit_price' => 75000, 'stock' => 0, 'unit' => 'unit']);
        SparePart::updateOrCreate(['sku' => 'SP-LMP-001'], ['name' => 'Lampu LED', 'unit_price' => 45000, 'stock' => 0, 'unit' => 'unit']);

        $r1 = DamageReport::updateOrCreate(['report_number' => 'DR-DEMO-001'], ['reporter_id' => $pengguna->id, 'asset_id' => $assets[1]->id, 'description' => 'AC menyala tetapi tidak mengeluarkan udara dingin sejak pagi.', 'reported_at' => now()->subHours(3), 'status' => 'Submitted', 'verified_by' => null, 'verification_note' => null]);
        $r2 = DamageReport::updateOrCreate(['report_number' => 'DR-DEMO-002'], ['reporter_id' => $pengguna->id, 'asset_id' => $assets[2]->id, 'description' => 'Proyektor menampilkan gambar yang berkedip ketika digunakan.', 'reported_at' => now()->subDay(), 'status' => 'Verified', 'verified_by' => $sarpras->id, 'verification_note' => 'Perlu pemeriksaan koneksi dan modul proyektor.']);
        $r3 = DamageReport::updateOrCreate(['report_number' => 'DR-DEMO-003'], ['reporter_id' => $pengguna->id, 'asset_id' => $assets[3]->id, 'description' => 'Lampu ruang B101 mati dan perlu diganti.', 'reported_at' => now()->subMonths(2), 'status' => 'Verified', 'verified_by' => $sarpras->id, 'verification_note' => 'Penggantian komponen disetujui.']);
        $r4 = DamageReport::updateOrCreate(['report_number' => 'DR-DEMO-004'], ['reporter_id' => $pengguna->id, 'asset_id' => $assets[0]->id, 'description' => 'AC mengeluarkan bunyi tidak normal saat mulai beroperasi.', 'reported_at' => now()->subDays(3), 'status' => 'Verified', 'verified_by' => $sarpras->id, 'verification_note' => 'Perlu dijadwalkan pemeriksaan teknisi.']);

        $completedStart = now()->subMonths(2)->startOfDay()->addHours(9);
        $completedEnd = $completedStart->copy()->addMinutes(180);

        $t1 = MaintenanceTicket::updateOrCreate(['damage_report_id' => $r2->id], ['ticket_number' => 'TCK-DEMO-001', 'status' => 'In Progress', 'opened_at' => now()->subDay(), 'sla_deadline' => now()->addHours(8)]);
        $t2 = MaintenanceTicket::updateOrCreate(['damage_report_id' => $r3->id], ['ticket_number' => 'TCK-DEMO-002', 'status' => 'Completed', 'opened_at' => $completedStart->copy()->subHour(), 'sla_deadline' => $completedStart->copy()->addHours(24)]);
        MaintenanceTicket::updateOrCreate(['damage_report_id' => $r4->id], ['ticket_number' => 'TCK-DEMO-003', 'status' => 'Open', 'opened_at' => now()->subDays(3), 'sla_deadline' => now()->subDays(2)]);
        MaintenanceTicket::updateOrCreate(['damage_report_id' => $r1->id], ['ticket_number' => 'TCK-DEMO-004', 'status' => 'Pending Verification', 'opened_at' => $r1->reported_at, 'sla_deadline' => $r1->reported_at->copy()->addHours(24)]);

        $wo1 = WorkOrder::updateOrCreate(['work_order_number' => 'WO-DEMO-001'], ['maintenance_ticket_id' => $t1->id, 'maintenance_type_id' => $corrective->id, 'technician_id' => $teknisi->id, 'status' => 'In Progress', 'started_at' => now()->subHours(2), 'completed_at' => null, 'labor_cost' => 150000, 'notes' => 'Pemeriksaan awal sedang dilakukan.']);
        $wo2 = WorkOrder::updateOrCreate(['work_order_number' => 'WO-DEMO-002'], ['maintenance_ticket_id' => $t2->id, 'maintenance_type_id' => $corrective->id, 'technician_id' => $teknisi->id, 'status' => 'Completed', 'started_at' => $completedStart, 'completed_at' => $completedEnd, 'labor_cost' => 100000, 'notes' => 'Modul lampu diganti dan fungsi diuji.']);
        $history = MaintenanceHistory::updateOrCreate(['work_order_id' => $wo2->id], ['asset_id' => $assets[3]->id, 'downtime_minutes' => 180, 'total_cost' => 145000, 'sla_breach' => false, 'service_date' => $completedEnd]);
        $wo2->spareParts()->syncWithoutDetaching([$this->partId('SP-LMP-001') => ['quantity' => 1, 'unit_price' => 45000]]);
    }
    private function partId(string $sku): int { return (int) SparePart::where('sku', $sku)->value('id'); }
}
