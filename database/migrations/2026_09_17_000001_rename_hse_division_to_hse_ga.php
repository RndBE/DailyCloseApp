<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename divisi 'HSE' menjadi 'HSE GA'.
 *
 * Nama divisi disimpan sebagai string di kolom users.division dan sebagai
 * elemen array di users.managed_divisions (JSON), bukan lewat tabel master,
 * jadi data lama perlu ditulis ulang di sini.
 */
return new class extends Migration
{
    private const OLD = 'HSE';

    private const NEW = 'HSE GA';

    public function up(): void
    {
        $this->rename(self::OLD, self::NEW);
    }

    public function down(): void
    {
        $this->rename(self::NEW, self::OLD);
    }

    private function rename(string $from, string $to): void
    {
        DB::table('users')->where('division', $from)->update(['division' => $to]);

        // managed_divisions dibaca per baris agar aman di semua driver:
        // JSON array tidak punya operasi "replace elemen" yang portabel.
        DB::table('users')
            ->whereNotNull('managed_divisions')
            ->select('id', 'managed_divisions')
            ->chunkById(200, function ($rows) use ($from, $to) {
                foreach ($rows as $row) {
                    $divisions = json_decode($row->managed_divisions, true);

                    if (! is_array($divisions) || ! in_array($from, $divisions, true)) {
                        continue;
                    }

                    $updated = array_values(array_unique(array_map(
                        fn ($division) => $division === $from ? $to : $division,
                        $divisions
                    )));

                    DB::table('users')
                        ->where('id', $row->id)
                        ->update(['managed_divisions' => json_encode($updated)]);
                }
            });
    }
};
