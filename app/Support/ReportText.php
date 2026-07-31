<?php

namespace App\Support;

class ReportText
{
    public static function splitBullets(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [''];
        }

        $text = self::stripInvisibleFormatCharacters($text);
        $result = [];

        foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
            foreach (preg_split('/\s*(?:^|\s)-\s+/u', $line) as $part) {
                $part = preg_replace('/^[\s\-\x{2022}\x{00B7}]+|[\s\-\x{2022}\x{00B7}]+$/u', '', $part);

                if ($part !== '') {
                    $result[] = $part;
                }
            }
        }

        return $result ?: [trim($text)];
    }

    private static function stripInvisibleFormatCharacters(string $text): string
    {
        return preg_replace('/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/u', '', $text);
    }
}
