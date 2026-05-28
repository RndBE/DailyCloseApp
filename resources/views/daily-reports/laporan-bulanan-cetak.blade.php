<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan — {{ $monthLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; background: #e5e5e5; }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #fff;
            padding: 18mm 18mm 18mm 22mm;
            box-shadow: 0 2px 12px rgba(0,0,0,.15);
        }

        /* Kop */
        .kop { border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 14px; text-align: center; }
        .kop .org { font-size: 15pt; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
        .kop .doc-title { font-size: 12pt; font-weight: bold; margin-top: 4px; }
        .kop .doc-sub { font-size: 10pt; color: #444; margin-top: 3px; }

        .doc-info { display: flex; justify-content: space-between; font-size: 9.5pt; color: #333;
                    border-bottom: 1px solid #999; padding-bottom: 8px; margin-bottom: 16px; }

        /* Divisi */
        .divisi-block { margin-bottom: 24px; page-break-inside: avoid; }
        .divisi-header {
            background: #1e3a5f;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color: #fff;
            padding: 5px 10px;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            border: 2px solid #1e3a5f;
        }
        .divisi-header .sub { font-size: 9pt; font-weight: normal; opacity: .85; }

        /* Tabel rekapitulasi */
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        thead th {
            background: #e8edf5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            border: 1px solid #aaa;
            padding: 4px 6px;
            text-align: center;
            font-weight: bold;
        }
        thead th.left { text-align: left; }
        tbody td { border: 1px solid #ccc; padding: 4px 6px; vertical-align: top; }
        tbody tr:nth-child(even) td { background: #f9fafb; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .text-center { text-align: center; }
        .text-muted { color: #666; }
        .bold { font-weight: bold; }

        /* Progress bar sederhana */
        .pct-bar { height: 5px; background: #e2e8f0; border-radius: 2px; margin-top: 3px; }
        .pct-fill { height: 5px; border-radius: 2px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .pct-good { background: #22c55e; }
        .pct-mid  { background: #f59e0b; }
        .pct-low  { background: #ef4444; }

        /* Tabel detail per hari */
        .detail-wrap { margin-top: 8px; }
        .detail-title { font-size: 9.5pt; font-weight: bold; margin-bottom: 4px; color: #1e3a5f; }
        .detail-table thead th { font-size: 9pt; padding: 3px 5px; }
        .detail-table tbody td { font-size: 9pt; padding: 3px 5px; }

        /* Footer */
        .doc-footer {
            margin-top: 30px; border-top: 2px solid #000; padding-top: 10px;
            display: flex; justify-content: space-between; font-size: 9.5pt;
            page-break-inside: avoid; break-inside: avoid;
        }
        .ttd-block { text-align: center; width: 160px; page-break-inside: avoid; break-inside: avoid; }
        .ttd-block .ttd-label { margin-bottom: 55px; }
        .ttd-block .ttd-line { border-top: 1px solid #000; padding-top: 4px; font-weight: bold; }

        .print-toolbar {
            width: 210mm; margin: 0 auto 10px;
            display: flex; justify-content: flex-end; gap: 8px;
        }
        .btn-print, .btn-close {
            padding: 6px 18px; border: none; border-radius: 4px;
            cursor: pointer; font-size: 13px; font-family: sans-serif;
        }
        .btn-print { background: #1e3a5f; color: #fff; }
        .btn-close  { background: #e2e8f0; color: #333; }

        @media print {
            body { background: #fff; }
            .print-toolbar { display: none; }
            .page { width: 100%; margin: 0; padding: 15mm 15mm 15mm 20mm; box-shadow: none; }
            .divisi-block { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="print-toolbar">
    <button class="btn-close" onclick="window.close()">✕ Tutup</button>
    <button class="btn-print" onclick="window.print()">🖨 Cetak Dokumen</button>
</div>

<div class="page">

    <div class="kop">
        <div class="org">Laporan Harian Tim</div>
        <div class="doc-title">Rekapitulasi Laporan Kerja Bulanan</div>
        <div class="doc-sub">Periode: {{ $monthLabel }}</div>
    </div>

    <div class="doc-info">
        <span>Tanggal cetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</span>
        <span>Dibuat oleh: {{ $generatedBy }}</span>
        <span>Total anggota: {{ $totalStats['members'] }} orang</span>
    </div>

    {{-- Ringkasan global --}}
    <table style="margin-bottom:20px">
        <thead>
            <tr>
                <th>Total Anggota</th>
                <th>Total Laporan Terkirim</th>
                <th>Total Lembur</th>
                <th>Butuh Bantuan</th>
                <th>Sanksi Terlambat</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center bold">{{ $totalStats['members'] }}</td>
                <td class="text-center bold">{{ $totalStats['submitted'] }}</td>
                <td class="text-center bold">{{ $totalStats['overtime'] }}</td>
                <td class="text-center bold">{{ $totalStats['need_help'] }}</td>
                <td class="text-center bold">{{ $totalStats['late'] }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Per Divisi --}}
    @forelse($byDivision as $division => $rows)
        <div class="divisi-block">
            <div class="divisi-header">
                <span>Divisi {{ $division }}</span>
                <span class="sub">{{ $rows->count() }} anggota — {{ $totalDays }} hari</span>
            </div>

            {{-- Tabel rekapitulasi anggota --}}
            <table>
                <thead>
                    <tr>
                        <th class="left" style="width:160px">Nama</th>
                        <th class="left" style="width:70px">Level</th>
                        <th style="width:110px">Laporan Terkirim</th>
                        <th style="width:70px">Lembur</th>
                        <th style="width:80px">Butuh Bantuan</th>
                        <th style="width:70px">Sanksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php $pct = $totalDays > 0 ? round($row->submitted / $totalDays * 100) : 0; @endphp
                        <tr>
                            <td>
                                <div class="bold">{{ $row->user->name }}</div>
                                @if($row->user->position)
                                    <div class="text-muted" style="font-size:8.5pt">{{ $row->user->position }}</div>
                                @endif
                            </td>
                            <td class="text-muted">{{ $row->user->level_name }}</td>
                            <td class="text-center">
                                <div class="bold">{{ $row->submitted }}/{{ $totalDays }}</div>
                                <div class="pct-bar">
                                    <div class="pct-fill {{ $pct >= 80 ? 'pct-good' : ($pct >= 50 ? 'pct-mid' : 'pct-low') }}"
                                         style="width:{{ $pct }}%"></div>
                                </div>
                                <div style="font-size:8.5pt; color:#666">{{ $pct }}%</div>
                            </td>
                            <td class="text-center">{{ $row->overtime > 0 ? $row->overtime.'x' : '—' }}</td>
                            <td class="text-center">{{ $row->need_help > 0 ? $row->need_help.'x' : '—' }}</td>
                            <td class="text-center bold {{ $row->late > 0 ? 'text-muted' : '' }}">
                                {{ $row->late > 0 ? $row->late.'x' : '—' }}
                            </td>
                        </tr>

                        {{-- Detail laporan per hari --}}
                        @if($row->reports->isNotEmpty())
                            <tr>
                                <td colspan="6" style="padding:4px 8px 10px; background:#fafafa">
                                    <div class="detail-wrap">
                                        <div class="detail-title">Detail laporan {{ $row->user->name }}:</div>
                                        <table class="detail-table">
                                            <thead>
                                                <tr>
                                                    <th class="left" style="width:90px">Tanggal</th>
                                                    <th class="left">Pekerjaan Diselesaikan</th>
                                                    <th style="width:65px">Jam Selesai</th>
                                                    <th style="width:55px">Lembur</th>
                                                    <th style="width:55px">Sanksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($row->reports as $r)
                                                    <tr>
                                                        <td>{{ $r->report_date->translatedFormat('d M Y') }}</td>
                                                        <td>{{ Str::limit($r->completed_work, 70) }}</td>
                                                        <td class="text-center">{{ substr($r->work_finished_at, 0, 5) }}</td>
                                                        <td class="text-center">{{ $r->overtime_status ? 'Ya' : '—' }}</td>
                                                        <td class="text-center">{{ $r->is_late ? 'Sanksi' : '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p style="text-align:center; color:#777; padding:20px 0; font-style:italic">
            Tidak ada data untuk periode ini.
        </p>
    @endforelse

    <div class="doc-footer">
        <div>
            <p>Dokumen ini dibuat secara otomatis oleh sistem.</p>
            <p style="margin-top:4px; color:#555">Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
        </div>
        <div class="ttd-block">
            <div class="ttd-label">Mengetahui,</div>
            <div class="ttd-line">{{ $generatedBy }}</div>
        </div>
    </div>

</div>

<script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
