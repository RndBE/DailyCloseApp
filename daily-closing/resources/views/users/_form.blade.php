@csrf

@if ($errors->any())
    <div class="alert border-0 mb-3" style="background:#fdecec;color:#b02a37;border-radius:12px;">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Terdapat kesalahan:</div>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

@php
    $managedSelected = old('managed_divisions', $user->managed_divisions ?? []);
    if (! is_array($managedSelected)) $managedSelected = [];
@endphp

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-person me-2 text-muted"></i>Data User</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Password {!! $user->exists ? '' : '<span class="text-danger">*</span>' !!}</label>
                <input type="password" name="password" class="form-control" {{ $user->exists ? '' : 'required' }}>
                @if($user->exists)<div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>@endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-shield-lock me-2 text-muted"></i>Peran &amp; Hak Akses</div>
    <div class="card-body">
        <div class="row g-3 align-items-start">
            <div class="col-md-6">
                <label class="form-label">Level Hierarki <span class="text-danger">*</span></label>
                <select name="level" id="level-select" class="form-select" required>
                    @foreach(\App\Models\User::LEVEL_NAMES as $lvl => $name)
                        <option value="{{ $lvl }}" @selected(old('level', $user->level)==$lvl)>Level {{ $lvl }} – {{ $name }}</option>
                    @endforeach
                </select>
                <div class="form-text">Menentukan laporan siapa yang bisa user lihat.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Peran Super Admin</label>
                <div class="p-3" style="background:var(--bg-soft);border-radius:10px;border:1px solid var(--line)">
                    <input type="hidden" name="is_super_admin" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="is_super_admin" name="is_super_admin" value="1"
                               @checked(old('is_super_admin', $user->is_super_admin))>
                        <label class="form-check-label" for="is_super_admin">
                            <span class="fw-semibold">Super Admin Sistem</span>
                            <div class="small text-muted">Dapat mengelola data user (CRUD), terlepas dari level hierarki.</div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label">Divisi</label>
                @php $currentDiv = old('division', $user->division); @endphp
                <select name="division" class="form-select">
                    <option value="">— Pilih divisi —</option>
                    @foreach(\App\Models\User::DIVISIONS as $d)
                        <option value="{{ $d }}" @selected($currentDiv === $d)>{{ $d }}</option>
                    @endforeach
                    @if($currentDiv && ! in_array($currentDiv, \App\Models\User::DIVISIONS, true))
                        <option value="{{ $currentDiv }}" selected>{{ $currentDiv }} (lama)</option>
                    @endif
                </select>
                <div class="form-text">Divisi tempat user ini bekerja.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Jabatan</label>
                @php $currentPos = old('position', $user->position); @endphp
                <select name="position" class="form-select">
                    <option value="">— Pilih jabatan —</option>
                    @foreach(\App\Models\User::POSITIONS as $p)
                        <option value="{{ $p }}" @selected($currentPos === $p)>{{ $p }}</option>
                    @endforeach
                    @if($currentPos && ! in_array($currentPos, \App\Models\User::POSITIONS, true))
                        <option value="{{ $currentPos }}" selected>{{ $currentPos }} (lama)</option>
                    @endif
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="is_active" class="form-select">
                    <option value="1" @selected(old('is_active', $user->is_active))>Aktif</option>
                    <option value="0" @selected(! old('is_active', $user->is_active))>Nonaktif</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3" id="managed-divisions-card" style="display:none;">
    <div class="card-header"><i class="bi bi-diagram-3 me-2 text-muted"></i>Divisi yang Dibawahi <span class="text-muted small fw-normal">(khusus Manager)</span></div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Manager dapat membawahi <strong>beberapa divisi</strong>. Centang divisi-divisi yang dia bawahi.
            Manager hanya akan melihat laporan dari divisi yang dicentang.
        </p>

        <div class="row g-2">
            @foreach(\App\Models\User::DIVISIONS as $d)
                <div class="col-md-4 col-sm-6">
                    <div class="form-check p-2" style="background:var(--bg-soft);border:1px solid var(--line);border-radius:8px;">
                        <input class="form-check-input managed-div-check" type="checkbox"
                               name="managed_divisions[]" value="{{ $d }}"
                               id="md-{{ \Illuminate\Support\Str::slug($d) }}"
                               @checked(in_array($d, $managedSelected, true))>
                        <label class="form-check-label small" for="md-{{ \Illuminate\Support\Str::slug($d) }}">
                            {{ $d }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="md-select-all"><i class="bi bi-check-all me-1"></i>Pilih semua</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="md-clear-all"><i class="bi bi-x-lg me-1"></i>Kosongkan</button>
            <span class="ms-auto text-muted small align-self-center"><span id="md-count">0</span> divisi dipilih</span>
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Batal</a>
    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
</div>

@push('scripts')
<script>
(function() {
    const levelSelect = document.getElementById('level-select');
    const managedCard = document.getElementById('managed-divisions-card');
    const checks      = document.querySelectorAll('.managed-div-check');
    const countEl     = document.getElementById('md-count');
    const MANAGER_LEVEL = {{ \App\Models\User::LEVEL_MANAGER }};

    function updateCount() {
        const n = Array.from(checks).filter(c => c.checked).length;
        countEl.textContent = n;
    }
    function toggleCard() {
        const show = parseInt(levelSelect.value, 10) === MANAGER_LEVEL;
        managedCard.style.display = show ? '' : 'none';
    }

    levelSelect.addEventListener('change', toggleCard);
    checks.forEach(c => c.addEventListener('change', updateCount));

    document.getElementById('md-select-all').addEventListener('click', () => {
        checks.forEach(c => c.checked = true); updateCount();
    });
    document.getElementById('md-clear-all').addEventListener('click', () => {
        checks.forEach(c => c.checked = false); updateCount();
    });

    toggleCard();
    updateCount();
})();
</script>
@endpush
