@csrf

@if ($errors->any())
    <div class="alert border-0 mb-3" style="background:#fdecec;color:#b02a37;border-radius:12px;">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Terdapat kesalahan pada input:</div>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-calendar3 me-2 text-muted"></i>Informasi Umum</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal Laporan <span class="text-danger">*</span></label>
                <input type="date" name="report_date" class="form-control"
                       value="{{ old('report_date', optional($report->report_date)->format('Y-m-d') ?? $report->report_date) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nama</label>
                <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label">Divisi / Jabatan</label>
                <input type="text" class="form-control" value="{{ trim((auth()->user()->division ?? '—') . ' / ' . (auth()->user()->position ?? '—'), ' /') }}" disabled>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-stopwatch me-2 text-muted"></i>Status Lembur</div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Apakah Lembur? <span class="text-danger">*</span></label>
                <select name="overtime_status" id="overtime_status" class="form-select" required>
                    <option value="0" {{ old('overtime_status', $report->overtime_status) ? '' : 'selected' }}>Tidak</option>
                    <option value="1" {{ old('overtime_status', $report->overtime_status) ? 'selected' : '' }}>Ya</option>
                </select>
            </div>
            <div class="col-md-4 overtime-only">
                <label class="form-label">Jam Mulai Lembur</label>
                <input type="time" name="overtime_start" class="form-control"
                       value="{{ old('overtime_start', optional($report->overtime_start)->substr(0,5) ?? substr($report->overtime_start ?? '', 0, 5)) }}">
            </div>
            <div class="col-md-4 overtime-only">
                <label class="form-label">Jam Selesai Lembur</label>
                <input type="time" name="overtime_end" class="form-control"
                       value="{{ old('overtime_end', substr($report->overtime_end ?? '', 0, 5)) }}">
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-clipboard2-check me-2 text-muted"></i>Detail Pekerjaan</div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">1️⃣ Pekerjaan yang sudah selesai <span class="text-danger">*</span></label>
            <textarea name="completed_work" rows="4" class="form-control" placeholder="- Item 1&#10;- Item 2" required>{{ old('completed_work', $report->completed_work) }}</textarea>
            <div class="form-text">Bisa diisi multi item, gunakan tanda strip (-) di awal baris.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">2️⃣ Pekerjaan yang belum selesai</label>
            <textarea name="unfinished_work" rows="3" class="form-control" placeholder="Kosongkan jika tidak ada">{{ old('unfinished_work', $report->unfinished_work) }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">3️⃣ Hambatan yang ada</label>
            <textarea name="obstacles" rows="3" class="form-control" placeholder="Kosongkan jika tidak ada">{{ old('obstacles', $report->obstacles) }}</textarea>
        </div>

        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label">4️⃣ Butuh bantuan leader? <span class="text-danger">*</span></label>
                <select name="need_leader_help" id="need_leader_help" class="form-select" required>
                    <option value="0" {{ old('need_leader_help', $report->need_leader_help) ? '' : 'selected' }}>Tidak</option>
                    <option value="1" {{ old('need_leader_help', $report->need_leader_help) ? 'selected' : '' }}>Ya</option>
                </select>
            </div>
            <div class="col-md-8 help-only">
                <label class="form-label">Penjelasan bantuan</label>
                <textarea name="leader_help_description" rows="2" class="form-control" placeholder="Jelaskan bantuan yang dibutuhkan">{{ old('leader_help_description', $report->leader_help_description) }}</textarea>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">5️⃣ Planning besok <span class="text-danger">*</span></label>
            <textarea name="tomorrow_plan" rows="3" class="form-control" required>{{ old('tomorrow_plan', $report->tomorrow_plan) }}</textarea>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-card-text me-2 text-muted"></i>Penutup</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Jam selesai kerja <span class="text-danger">*</span></label>
                <input type="time" name="work_finished_at" class="form-control"
                       value="{{ old('work_finished_at', substr($report->work_finished_at ?? '', 0, 5)) }}" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Catatan tambahan</label>
                <textarea name="additional_notes" rows="2" class="form-control" placeholder="Opsional">{{ old('additional_notes', $report->additional_notes) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-x-lg me-1"></i> Batal
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i> Simpan Laporan
    </button>
</div>

@push('scripts')
<script>
    function toggleOvertime() {
        const v = document.getElementById('overtime_status').value === '1';
        document.querySelectorAll('.overtime-only').forEach(el => el.style.display = v ? '' : 'none');
    }
    function toggleHelp() {
        const v = document.getElementById('need_leader_help').value === '1';
        document.querySelectorAll('.help-only').forEach(el => el.style.display = v ? '' : 'none');
    }
    document.getElementById('overtime_status').addEventListener('change', toggleOvertime);
    document.getElementById('need_leader_help').addEventListener('change', toggleHelp);
    toggleOvertime();
    toggleHelp();
</script>
@endpush
