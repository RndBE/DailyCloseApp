@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i>Manajemen User</h2>
        <p class="text-muted mb-0 small">Kelola data user, level akses, divisi, dan status aktif. User tidak dihapus permanen agar riwayat laporan tetap aman.</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Tambah User
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Nama, email, atau divisi…">
            </div>
            <div class="col-md-3">
                <label class="form-label">Level</label>
                <select name="level" class="form-select">
                    <option value="">Semua Level</option>
                    @foreach(\App\Models\User::LEVEL_NAMES as $lvl => $name)
                        <option value="{{ $lvl }}" @selected(request('level')==$lvl)>Level {{ $lvl }} – {{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2 align-items-end justify-content-end">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Reset</a>
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Cari</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Level</th>
                    <th>Divisi / Jabatan</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar">{{ strtoupper(substr($u->name,0,1)) }}</div>
                                <div class="fw-semibold">{{ $u->name }}</div>
                            </div>
                        </td>
                        <td class="text-muted small">{{ $u->email }}</td>
                        <td>
                            @php
                                $lvlClass = ['1'=>'bg-soft-primary','2'=>'bg-soft-info','3'=>'bg-soft-success','4'=>'bg-soft-warning'][$u->level] ?? 'bg-soft-primary';
                            @endphp
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge-soft {{ $lvlClass }}">L{{ $u->level }} – {{ $u->level_name }}</span>
                                @if($u->is_super_admin)
                                    <span class="badge-soft" style="background:#fdecec;color:#b02a37"><i class="bi bi-shield-lock"></i> Super Admin</span>
                                @endif
                                @if(is_null($u->company_id))
                                    <span class="badge-soft" style="background:#eef2ff;color:#4f46e5"><i class="bi bi-globe2"></i> Global</span>
                                @endif
                            </div>
                        </td>
                        <td class="small">
                            <div>{{ $u->division ?? '—' }}</div>
                            <div class="text-muted">{{ $u->position ?? '—' }}</div>
                            @if(in_array($u->level, [\App\Models\User::LEVEL_MANAGER, \App\Models\User::LEVEL_LEADER], true) && !empty($u->managed_divisions))
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    @foreach($u->managed_divisions as $md)
                                        <span class="badge-soft bg-soft-primary" style="font-size:.7rem; padding:.15rem .45rem"><i class="bi bi-diagram-3"></i> {{ $md }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($u->is_active)
                                <span class="badge-soft bg-soft-success"><i class="bi bi-check-circle"></i> Aktif</span>
                            @else
                                <span class="badge-soft" style="background:#fdecec;color:#b02a37"><i class="bi bi-slash-circle"></i> Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($u->id !== auth()->id() && $u->is_active)
                                <form action="{{ route('users.destroy', $u) }}" method="POST"
                                      onsubmit="return confirm('Nonaktifkan user {{ $u->name }}?');" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-soft-danger" title="Nonaktifkan"><i class="bi bi-power me-1"></i>Nonaktifkan</button>
                                </form>
                            @elseif($u->id !== auth()->id())
                                <span class="text-muted small">Sudah nonaktif</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-people display-5 d-block mb-2 text-muted opacity-50"></i>
                            Tidak ada user ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="p-3 border-top">{{ $users->links() }}</div>
    @endif
</div>
@endsection
