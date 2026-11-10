@php
    $selectedFloor = old(
        'floor_code',
        isset($asset) && $asset->location
            ? 'B' . $asset->location->floor
            : ''
    );

    $selectedRoom = old(
        'room',
        isset($asset) && $asset->location
            ? $asset->location->room
            : ''
    );
@endphp

<div class="form-grid">
    <div class="field">
        <label for="asset_code">Kode aset *</label>
        <input
            id="asset_code"
            name="asset_code"
            value="{{ old('asset_code', $asset->asset_code ?? '') }}"
            placeholder="AC-B101-01"
            required
        >
    </div>

    <div class="field">
        <label for="name">Nama aset *</label>
        <input
            id="name"
            name="name"
            value="{{ old('name', $asset->name ?? '') }}"
            placeholder="AC Ruang B101"
            required
        >
    </div>

    <div class="field">
        <label for="category_id">Kategori *</label>
        <select id="category_id" name="category_id" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option
                    value="{{ $category->id }}"
                    @selected(old('category_id', $asset->category_id ?? '') == $category->id)
                >
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="floor_code">Lantai / Kode Lantai *</label>
        <select id="floor_code" name="floor_code" required>
            <option value="">Pilih lantai</option>
            <option value="B1" @selected($selectedFloor === 'B1')>
                B1 — Lantai 1
            </option>
            <option value="B2" @selected($selectedFloor === 'B2')>
                B2 — Lantai 2
            </option>
            <option value="B3" @selected($selectedFloor === 'B3')>
                B3 — Lantai 3
            </option>
        </select>
    </div>

    <div class="field">
        <label for="room">Kelas / Ruang *</label>
        <input
            id="room"
            name="room"
            value="{{ $selectedRoom }}"
            maxlength="40"
            placeholder="Contoh: B101 atau C201"
            required
        >
        <small>
            Ketik sendiri kode atau nomor kelas/ruangan.
        </small>
    </div>

    <div class="field">
        <label for="condition">Kondisi *</label>
        <select id="condition" name="condition" required>
            @foreach (['Baik', 'Perlu Perbaikan', 'Rusak'] as $condition)
                <option
                    value="{{ $condition }}"
                    @selected(old('condition', $asset->condition ?? 'Baik') === $condition)
                >
                    {{ $condition }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="purchase_date">Tanggal pembelian</label>
        <input
            id="purchase_date"
            type="date"
            name="purchase_date"
            value="{{ old(
                'purchase_date',
                isset($asset) && $asset->purchase_date
                    ? $asset->purchase_date->format('Y-m-d')
                    : ''
            ) }}"
        >
    </div>

    <div class="field">
        <label for="purchase_price">Harga pembelian (Rp) *</label>
        <input
            id="purchase_price"
            type="number"
            name="purchase_price"
            min="0"
            step="0.01"
            value="{{ old('purchase_price', $asset->purchase_price ?? 0) }}"
            required
        >
        <small>
            Digunakan sebagai komponen perhitungan Life-Cycle Cost.
        </small>
    </div>
</div>

<div class="form-actions">
    <a href="{{ route('assets.index') }}" class="btn btn-outline">
        Batal
    </a>

    <button class="btn btn-primary" type="submit">
        {{ isset($asset) ? 'Simpan perubahan' : 'Simpan aset' }} →
    </button>
</div>
