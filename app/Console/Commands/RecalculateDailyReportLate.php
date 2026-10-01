<?php

namespace App\Console\Commands;

use App\Models\DailyReport;
use App\Support\CompanyContext;
use App\Support\DailyReportDeadline;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Hitung ulang kolom is_late laporan harian dengan aturan batas H+1 pukul 10:00 WIB.
 *
 * Waktu kirim memakai created_at laporan. Tanpa --apply hanya menampilkan perubahan.
 * Dengan --apply, nilai lama disimpan ke storage/app/backups agar bisa dikembalikan.
 */
class RecalculateDailyReportLate extends Command
{
    protected $signature = 'daily-reports:recalculate-late
        {--from=2026-09-01 : Tanggal laporan awal (Y-m-d)}
        {--to= : Tanggal laporan akhir (Y-m-d), default hari ini}
        {--apply : Simpan perubahan; tanpa ini hanya dry-run}';

    protected $description = 'Hitung ulang status telat laporan harian dengan batas H+1 pukul 10:00 WIB';

    public function handle(): int
    {
        CompanyContext::clear();

        $from = Carbon::parse((string) $this->option('from'))->toDateString();
        $to = Carbon::parse((string) ($this->option('to') ?: now()->toDateString()))->toDateString();
        $apply = (bool) $this->option('apply');

        $reports = DailyReport::withoutGlobalScopes()
            ->with(['user' => fn ($query) => $query->withoutGlobalScopes()])
            ->whereBetween('report_date', [$from, $to.' 23:59:59'])
            ->orderBy('report_date')
            ->orderBy('id')
            ->get();

        $changes = [];
        foreach ($reports as $report) {
            if (! $report->user) {
                continue;
            }

            $late = DailyReportDeadline::isLate($report->user, $report->report_date, $report->created_at);
            if ($late !== (bool) $report->is_late) {
                $changes[] = [$report, $late];
            }
        }

        $this->info(sprintf('%d laporan %s s/d %s diperiksa, %d berubah.', $reports->count(), $from, $to, count($changes)));
        $this->table(
            ['ID', 'Tanggal', 'User', 'Level', 'Dikirim', 'Lama', 'Baru'],
            array_map(fn ($change) => [
                $change[0]->id,
                $change[0]->report_date->toDateString(),
                $change[0]->user->name,
                $change[0]->user->level_name,
                $change[0]->created_at->format('Y-m-d H:i'),
                $change[0]->is_late ? 'telat' : '-',
                $change[1] ? 'telat' : '-',
            ], $changes)
        );

        if (! $apply || $changes === []) {
            if (! $apply) {
                $this->comment('Dry-run: tidak ada yang disimpan. Jalankan ulang dengan --apply untuk menyimpan.');
            }

            return self::SUCCESS;
        }

        $backup = storage_path('app/backups/daily-report-late-'.now()->format('Ymd-His').'.tsv');
        File::ensureDirectoryExists(dirname($backup));
        File::put($backup, "id\told_is_late\tnew_is_late\n".implode('', array_map(
            fn ($change) => $change[0]->id."\t".(int) $change[0]->is_late."\t".(int) $change[1]."\n",
            $changes
        )));

        foreach ($changes as [$report, $late]) {
            // toBase(): updated_at sengaja tidak disentuh, ini koreksi aturan, bukan suntingan laporan.
            DailyReport::withoutGlobalScopes()->whereKey($report->id)->toBase()->update(['is_late' => $late]);
        }

        $this->info('Tersimpan. Nilai lama: '.$backup);

        return self::SUCCESS;
    }
}
