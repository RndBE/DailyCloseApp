<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rangkuman Laporan Tim — {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            background: #e5e5e5;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #fff;
            padding: 20mm 20mm 20mm 25mm;
            box-shadow: 0 2px 12px rgba(0,0,0,.15);
        }

        /* Kop dokumen */
        .kop {
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 16px;
            text-align: center;
        }
        .kop .org-name {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .kop .doc-title {
            font-size: 13pt;
            font-weight: bold;
            margin-top: 4px;
        }
        .kop .doc-sub {
            font-size: 10pt;
            margin-top: 3px;
            color: #444;
        }

        /* Info dokumen */
        .doc-info {
            display: flex;
            justify-content: space-between;
            font-size: 10pt;
            margin-bottom: 20px;
            border-bottom: 1px solid #999;
            padding-bottom: 8px;
            color: #333;
        }

        /* Ringkasan global */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 10.5pt;
        }
        .summary-table th {
            background: #1e3a5f;
            color: #fff;
            padding: 5px 8px;
            text-align: left;
            font-weight: bold;
        }
        .summary-table td {
            padding: 4px 8px;
            border: 1px solid #ccc;
        }
        .summary-table tr:nth-child(even) td { background: #f5f7fa; }
        .summary-table .num { text-align: center; font-weight: bold; }

        /* Divisi */
        .divisi-block {
            margin-bottom: 28px;
            page-break-inside: avoid;
        }
        .divisi-header {
            background: #1e3a5f;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color: #fff;
            padding: 6px 10px;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid #1e3a5f;
        }
        .divisi-header .sub {
            font-size: 9pt;
            font-weight: normal;
            opacity: .85;
        }
        .divisi-body {
            border: 2px solid #1e3a5f;
            border-top: none;
            padding: 12px 14px;
        }

        /* Seksi bernomor */
        .section { margin-bottom: 12px; }
        .section-title {
            font-size: 10.5pt;
            font-weight: bold;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .section-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            min-width: 20px;
            background: #1e3a5f;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color: #fff;
            font-size: 8.5pt;
            border-radius: 50%;
            font-weight: bold;
        }
        .section ul {
            padding-left: 28px;
            margin: 0;
        }
        .section ul li {
            margin-bottom: 3px;
            line-height: 1.6;
        }
        .empty-text {
            color: #777;
            font-style: italic;
            padding-left: 28px;
            font-size: 10pt;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #ddd;
            margin: 12px 0;
        }

        /* Baris bawah: lembur & sanksi */
        .bottom-row {
            display: flex;
            gap: 16px;
            margin-top: 4px;
        }
        .bottom-box {
            flex: 1;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 8px 10px;
            font-size: 10pt;
        }
        .bottom-box .box-title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 10pt;
        }
        .bottom-box.lembur   { border-left: 3px solid #c07000; }
        .bottom-box.sanksi   { border-left: 3px solid #b02a37; }
        .bottom-box .box-title.lembur-title  { color: #c07000; }
        .bottom-box .box-title.sanksi-title  { color: #b02a37; }
        .bottom-box ul { padding-left: 16px; margin: 0; }
        .bottom-box ul li { line-height: 1.7; }

        /* Alert belum kirim */
        .alert-missing {
            background: #fff8e6;
            border: 1px solid #f0c040;
            border-left: 3px solid #c07000;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 10pt;
            border-radius: 2px;
        }

        /* Footer dokumen */
        .doc-footer {
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5pt;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .ttd-block {
            text-align: center;
            width: 160px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .ttd-block .ttd-label { margin-bottom: 60px; }
        .ttd-block .ttd-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-weight: bold;
        }

        /* Tombol cetak — hilang saat print */
        .print-toolbar {
            width: 210mm;
            margin: 0 auto 10px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn-print, .btn-close {
            padding: 6px 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-family: sans-serif;
        }
        .btn-print { background: #1e3a5f; color: #fff; }
        .btn-close { background: #e2e8f0; color: #333; }

        @media print {
            body { background: #fff; }
            .print-toolbar { display: none; }
            .page {
                width: 100%;
                margin: 0;
                padding: 15mm 15mm 15mm 20mm;
                box-shadow: none;
            }
            .divisi-block { page-break-inside: avoid; }
            .divisi-header, .section-num {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

<div class="print-toolbar">
    <button class="btn-close" onclick="window.close()">✕ Tutup</button>
    <button class="btn-print" onclick="window.print()">🖨 Cetak Dokumen</button>
</div>

<div class="page">

    {{-- Kop --}}
    <div class="kop">
        <div class="org-name">Laporan Harian Tim</div>
        <div class="doc-title">Rangkuman Laporan Kerja Harian</div>
        <div class="doc-sub">
            {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
        </div>
    </div>

    {{-- Info dokumen --}}
    <div class="doc-info">
        <span>Tanggal cetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</span>
        <span>Dibuat oleh: {{ $generatedBy }}</span>
        <span>Total anggota: {{ $totalStats['total'] }} orang</span>
    </div>

    {{-- Per Divisi --}}
    @forelse($byDivision as $division => $rows)
        @php
            $split = function(string $text): array {
                $result = [];
                foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
                    foreach (preg_split('/\s+-\s+/', $line) as $part) {
                        $part = trim($part, " \t-•·");
                        if ($part !== '') $result[] = $part;
                    }
                }
                return $result ?: [trim($text)];
            };

            $flatten = fn($col) => $col->flatMap(fn($v) => $split($v))->filter()->values();

            $submitted  = $rows->filter(fn($r) => $r->report);
            $missing    = $rows->filter(fn($r) => !$r->report);
            $overtime   = $submitted->filter(fn($r) => $r->report->overtime_status);
            $needHelp   = $submitted->filter(fn($r) => $r->report->need_leader_help);
            $lateRows   = $submitted->filter(fn($r) => $r->report->is_late);

            $completedWork = $flatten($submitted->map(fn($r) => trim($r->report->completed_work))->filter());
            $unfinished    = $flatten($submitted->map(fn($r) => trim($r->report->unfinished_work ?? ''))->filter());
            $obstacles     = $flatten($submitted->map(fn($r) => trim($r->report->obstacles ?? ''))->filter());
            $helpDesc      = $flatten($needHelp->map(fn($r) => trim($r->report->leader_help_description ?? ''))->filter());
            $plans         = $flatten($submitted->map(fn($r) => trim($r->report->tomorrow_plan))->filter());
            $notes         = $flatten($submitted->map(fn($r) => trim($r->report->additional_notes ?? ''))->filter());
        @endphp

        <div class="divisi-block">
            <div class="divisi-header">
                <span>Divisi {{ $division }}</span>
                <span class="sub">{{ $submitted->count() }}/{{ $rows->count() }} laporan masuk</span>
            </div>
            <div class="divisi-body">

                @if($missing->count() > 0)
                    <div class="alert-missing">
                        <strong>Belum mengirim laporan:</strong>
                        {{ $missing->map(fn($r) => $r->user->name)->join(', ') }}
                    </div>
                @endif

                @if($submitted->count() === 0)
                    <p class="empty-text">Tidak ada laporan yang masuk dari divisi ini.</p>
                @else
                    @php $n = 0; @endphp

                    {{-- 1. Pekerjaan diselesaikan --}}
                    <div class="section">
                        <div class="section-title">
                            <span class="section-num">{{ ++$n }}</span> Pekerjaan yang Diselesaikan
                        </div>
                        @if($completedWork->count() > 0)
                            <ul>
                                @foreach($completedWork as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="empty-text">Tidak ada data.</p>
                        @endif
                    </div>

                    {{-- 2. Belum selesai --}}
                    <div class="section">
                        <div class="section-title">
                            <span class="section-num">{{ ++$n }}</span> Pekerjaan yang Belum Selesai
                        </div>
                        @if($unfinished->count() > 0)
                            <ul>
                                @foreach($unfinished as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="empty-text">Tidak ada pekerjaan yang tertunda.</p>
                        @endif
                    </div>

                    {{-- 3. Hambatan --}}
                    <div class="section">
                        <div class="section-title">
                            <span class="section-num">{{ ++$n }}</span> Hambatan
                        </div>
                        @if($obstacles->count() > 0)
                            <ul>
                                @foreach($obstacles as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="empty-text">Tidak ada hambatan yang dilaporkan.</p>
                        @endif
                    </div>

                    {{-- 4. Kebutuhan bantuan --}}
                    <div class="section">
                        <div class="section-title">
                            <span class="section-num">{{ ++$n }}</span> Kebutuhan Bantuan Pimpinan
                        </div>
                        @if($needHelp->count() > 0)
                            @if($helpDesc->count() > 0)
                                <ul>
                                    @foreach($helpDesc as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <p style="padding-left:28px; font-size:10pt; margin-top:4px; color:#333">
                                <em>Diajukan oleh: {{ $needHelp->map(fn($r) => $r->user->name)->join(', ') }}</em>
                            </p>
                        @else
                            <p class="empty-text">Tidak ada permintaan bantuan.</p>
                        @endif
                    </div>

                    {{-- 5. Planning --}}
                    <div class="section">
                        <div class="section-title">
                            <span class="section-num">{{ ++$n }}</span> Rencana Kerja Hari Berikutnya
                        </div>
                        @if($plans->count() > 0)
                            <ul>
                                @foreach($plans as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="empty-text">Tidak ada data.</p>
                        @endif
                    </div>

                    {{-- 6. Catatan tambahan (kondisional) --}}
                    @if($notes->count() > 0)
                        <div class="section">
                            <div class="section-title">
                                <span class="section-num">{{ ++$n }}</span> Catatan Tambahan
                            </div>
                            <ul>
                                @foreach($notes as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <hr class="divider">

                    {{-- Lembur + Sanksi --}}
                    <div class="bottom-row">
                        <div class="bottom-box lembur">
                            <div class="box-title lembur-title">{{ ++$n }}. Yang Lembur</div>
                            @if($overtime->count() > 0)
                                <ul>
                                    @foreach($overtime as $row)
                                        <li>
                                            {{ $row->user->name }}
                                            @if($row->report->overtime_start && $row->report->overtime_end)
                                                &mdash; {{ substr($row->report->overtime_start,0,5) }} s/d {{ substr($row->report->overtime_end,0,5) }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p style="color:#777; font-style:italic; margin:0">Tidak ada lembur.</p>
                            @endif
                        </div>
                        <div class="bottom-box sanksi">
                            <div class="box-title sanksi-title">{{ ++$n }}. Sanksi Terlambat Kirim</div>
                            @if($lateRows->count() > 0)
                                <ul>
                                    @foreach($lateRows as $row)
                                        <li>
                                            {{ $row->user->name }}
                                            &mdash; kirim pukul {{ $row->report->created_at->translatedFormat('H:i') }} WIB
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p style="color:#777; font-style:italic; margin:0">Tidak ada sanksi.</p>
                            @endif
                        </div>
                    </div>

                @endif
            </div>
        </div>
    @empty
        <p style="text-align:center; color:#777; padding: 20px 0; font-style:italic">
            Tidak ada anggota tim yang ditemukan.
        </p>
    @endforelse

    {{-- Footer / Tanda tangan --}}
    <div class="doc-footer">
        <div>
            <p>Dokumen ini dibuat secara otomatis oleh sistem.</p>
            <p style="margin-top:4px; color:#555">Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
        </div>
        <div class="ttd-block">
            <div class="ttd-label">Manager / Pimpinan,</div>
            <div class="ttd-line">{{ $generatedBy }}</div>
        </div>
    </div>

</div>

<script>
    // Auto buka dialog print saat halaman terbuka
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
