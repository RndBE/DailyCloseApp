<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Generator jadwal shift security berbasis pola 3-minggu (A→B→C) yang
 * berputar terus-menerus (continuous, tidak reset tiap bulan).
 *
 * Anchor: Senin 1 Juni 2026 = Template A (indeks 0).
 *
 * Pola libur fixed per "slot orang" (urutan user yang dikirim ke generator):
 *   - Slot 0 (P0) : libur tiap Senin
 *   - Slot 1 (P1) : libur tiap Selasa
 *   - Slot 2 (P2) : libur tiap Rabu
 *
 * Senin–Rabu : 1 orang libur, 2 orang cover shift 12 jam (06-18 & 18-06).
 * Kamis–Minggu: semua masuk, shift 8 jam (S1 06-14 / S2 14-22 / S3 22-06).
 */
class SecurityScheduleGenerator
{
    /** Tanggal acuan rotasi (Senin) = Template A. */
    public const ANCHOR_DATE = '2026-06-01';

    /** Jumlah personel yang didukung pola ini. */
    public const REQUIRED_STAFF = 3;

    /**
     * Definisi shift: kode => [start_time, end_time, is_off].
     * Shift malam melewati tengah malam (end < start).
     */
    private const SHIFTS = [
        'off'     => [null, null, true],
        'day12'   => ['06:00:00', '18:00:00', false],
        'night12' => ['18:00:00', '06:00:00', false],
        's1'      => ['06:00:00', '14:00:00', false],
        's2'      => ['14:00:00', '22:00:00', false],
        's3'      => ['22:00:00', '06:00:00', false],
    ];

    /**
     * Template[idx][dayOfWeek] = [shift P0, shift P1, shift P2].
     * dayOfWeek: 1=Senin, 2=Selasa, 3=Rabu, 0/4/5/6=Kamis–Minggu.
     */
    private const TEMPLATES = [
        // Template A
        0 => [
            1 => ['off', 'day12', 'night12'],
            2 => ['day12', 'off', 'night12'],
            3 => ['day12', 'night12', 'off'],
            'weekend' => ['s1', 's3', 's2'],
        ],
        // Template B
        1 => [
            1 => ['off', 'night12', 'day12'],
            2 => ['night12', 'off', 'day12'],
            3 => ['night12', 'day12', 'off'],
            'weekend' => ['s2', 's3', 's1'],
        ],
        // Template C
        2 => [
            1 => ['off', 'night12', 'day12'],
            2 => ['night12', 'off', 'day12'],
            3 => ['night12', 'day12', 'off'],
            'weekend' => ['s2', 's1', 's3'],
        ],
    ];

    /**
     * Hasilkan baris jadwal untuk satu bulan.
     *
     * @param  array<int,int>  $userIds  ID user security, terurut → slot P0,P1,P2.
     * @return array<int,array{user_id:int,date:string,start_time:?string,end_time:?string,is_off:bool}>
     */
    public function generateForMonth(int $year, int $month, array $userIds): array
    {
        $userIds = array_values($userIds);
        if (count($userIds) !== self::REQUIRED_STAFF) {
            return [];
        }

        $anchor = Carbon::parse(self::ANCHOR_DATE)->startOfDay();
        $cursor = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end    = $cursor->copy()->endOfMonth();

        $rows = [];

        while ($cursor->lte($end)) {
            $templateIdx = $this->templateIndexFor($cursor, $anchor);
            $dow         = $cursor->dayOfWeek; // 0=Min .. 6=Sab
            $template    = self::TEMPLATES[$templateIdx];

            $assignments = in_array($dow, [1, 2, 3], true)
                ? $template[$dow]
                : $template['weekend'];

            foreach ($assignments as $slot => $shiftCode) {
                [$start, $endTime, $isOff] = self::SHIFTS[$shiftCode];

                $rows[] = [
                    'user_id'    => $userIds[$slot],
                    'date'       => $cursor->toDateString(),
                    'start_time' => $start,
                    'end_time'   => $endTime,
                    'is_off'     => $isOff,
                ];
            }

            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * Tentukan indeks template (0=A,1=B,2=C) untuk tanggal tertentu,
     * berdasarkan selisih minggu dari anchor.
     */
    private function templateIndexFor(Carbon $date, Carbon $anchor): int
    {
        $monday = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weeks  = (int) round($anchor->diffInDays($monday, false) / 7);

        return (($weeks % 3) + 3) % 3;
    }
}
