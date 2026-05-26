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

@push('styles')
<style>
    .access-option-panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: .85rem .95rem;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .access-option-panel:has(.form-check-input:checked) {
        background: var(--brand-50);
        border-color: rgba(var(--brand-rgb), .32);
        box-shadow: 0 8px 20px rgba(var(--brand-rgb), .08);
    }

    .managed-division-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: .65rem;
    }

    .managed-division-option {
        min-height: 54px;
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .75rem .85rem;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 12px;
        cursor: pointer;
        transition: transform .15s, border-color .15s, box-shadow .15s, background .15s;
    }

    .managed-division-option:hover {
        border-color: var(--ink-300);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .06);
        transform: translateY(-1px);
    }

    .managed-division-option:has(.managed-div-check:checked) {
        background: var(--brand-50);
        border-color: rgba(var(--brand-rgb), .4);
        box-shadow: inset 0 0 0 1px rgba(var(--brand-rgb), .08);
    }

    .managed-division-option .form-check-input {
        width: 1.05rem;
        height: 1.05rem;
        margin: 0;
        flex: 0 0 auto;
    }

    .managed-division-title {
        color: var(--ink-700);
        font-size: .88rem;
        font-weight: 600;
        line-height: 1.25;
    }

    .managed-division-count {
        background: var(--bg-soft);
        border: 1px solid var(--line);
        border-radius: 999px;
        padding: .35rem .65rem;
    }
</style>
@endpush

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
                <div class="access-option-panel">
                    <input type="hidden" name="is_super_admin" value="0">
                    <div class="form-check form-switch mb-0">
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
    <div class="card-header"><i class="bi bi-diagram-3 me-2 text-muted"></i>Divisi yang Dibawahi <span class="text-muted small fw-normal">(Manager & Leader)</span></div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Manager / Leader dapat membawahi <strong>beberapa divisi</strong>. Centang divisi-divisi yang dia bawahi.
            User hanya akan melihat laporan dari divisi yang dicentang. Jika dikosongkan, otomatis pakai divisi user itu sendiri.
        </p>

        <div class="managed-division-grid">
            @foreach(\App\Models\User::DIVISIONS as $d)
                <label class="managed-division-option" for="md-{{ \Illuminate\Support\Str::slug($d) }}">
                    <input class="form-check-input managed-div-check" type="checkbox"
                           name="managed_divisions[]" value="{{ $d }}"
                           id="md-{{ \Illuminate\Support\Str::slug($d) }}"
                           @checked(in_array($d, $managedSelected, true))>
                    <span class="managed-division-title">{{ $d }}</span>
                </label>
            @endforeach
        </div>

        <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="md-select-all"><i class="bi bi-check-all me-1"></i>Pilih semua</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="md-clear-all"><i class="bi bi-x-lg me-1"></i>Kosongkan</button>
            <span class="managed-division-count ms-sm-auto text-muted small"><span id="md-count">0</span> divisi dipilih</span>
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
    const MANAGED_LEVELS = [{{ \App\Models\User::LEVEL_MANAGER }}, {{ \App\Models\User::LEVEL_LEADER }}];

    function updateCount() {
        const n = Array.from(checks).filter(c => c.checked).length;
        countEl.textContent = n;
    }
    function toggleCard() {
        const show = MANAGED_LEVELS.includes(parseInt(levelSelect.value, 10));
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
