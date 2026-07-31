<?php

namespace Tests\Unit;

use App\Support\ReportText;
use PHPUnit\Framework\TestCase;

class ReportTextTest extends TestCase
{
    public function test_split_bullets_strips_invisible_format_characters_after_dash(): void
    {
        $items = ReportText::splitBullets("- Melakukan penyampaian\n- \u{2060}Melakukan diskusi\n\u{200B}- Melakukan cek KPI");

        $this->assertSame([
            'Melakukan penyampaian',
            'Melakukan diskusi',
            'Melakukan cek KPI',
        ], $items);

        foreach ($items as $item) {
            $this->assertDoesNotMatchRegularExpression('/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/u', $item);
        }
    }
}
