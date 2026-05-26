@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-person-gear text-primary me-2"></i>Edit User</h2>
        <p class="text-muted mb-0 small">Perbarui data user: {{ $user->name }}.</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
</div>

<form method="POST" action="{{ route('users.update', $user) }}">
    @method('PUT')
    @include('users._form', ['user' => $user])
</form>
@endsection
