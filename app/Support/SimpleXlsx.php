<?php

namespace App\Support;

class SimpleXlsx
{
    private array $rows = [];

    public function addRow(array $row, bool $bold = false): static
    {
        $this->rows[] = ['data' => $row, 'bold' => $bold];
        return $this;
    }

    public function addEmpty(): static
    {
        $this->rows[] = ['data' => [], 'bold' => false];
        return $this;
    }

    public function download(string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $content = $this->build();

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function build(): string
    {
        return $this->zip([
            '[Content_Types].xml'          => $this->contentTypes(),
            '_rels/.rels'                  => $this->rels(),
            'xl/workbook.xml'              => $this->workbook(),
            'xl/_rels/workbook.xml.rels'   => $this->workbookRels(),
            'xl/styles.xml'                => $this->styles(),
            'xl/worksheets/sheet1.xml'     => $this->worksheet(),
        ]);
    }

    // Pure PHP ZIP writer (no ZipArchive extension required)
    private function zip(array $files): string
    {
        $localBlocks = '';
        $central     = '';
        $offset      = 0;

        foreach ($files as $name => $content) {
            $crc      = crc32($content);
            $size     = strlen($content);
            $nameLen  = strlen($name);

            // Try deflate compression if zlib is available
            $compressed = function_exists('gzdeflate') ? gzdeflate($content, 6) : false;
            if ($compressed !== false && strlen($compressed) < $size) {
                $method   = 8; // deflate
                $compData = $compressed;
                $compSize = strlen($compressed);
            } else {
                $method   = 0; // stored
                $compData = $content;
                $compSize = $size;
            }

            $local = pack('VvvvvvVVVvv',
                0x04034b50, 20, 0, $method, 0, 0,
                $crc, $compSize, $size, $nameLen, 0
            ) . $name . $compData;

            $central .= pack('VvvvvvvVVVvvvvvVV',
                0x02014b50, 20, 20, 0, $method, 0, 0,
                $crc, $compSize, $size,
                $nameLen, 0, 0, 0, 0, 0,
                $offset
            ) . $name;

            $offset      += strlen($local);
            $localBlocks .= $local;
        }

        $cdSize   = strlen($central);
        $numFiles = count($files);

        $eocd = pack('VvvvvVVv',
            0x06054b50, 0, 0,
            $numFiles, $numFiles,
            $cdSize, $offset, 0
        );

        return $localBlocks . $central . $eocd;
    }

    private function colLetter(int $idx): string
    {
        $letter = '';
        $idx++;
        while ($idx > 0) {
            $mod    = ($idx - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $idx    = (int)(($idx - $mod) / 26);
        }
        return $letter;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function worksheet(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
             . '<sheetData>';

        foreach ($this->rows as $rowIdx => $row) {
            $style = $row['bold'] ? ' s="1"' : '';
            $xml .= '<row r="' . ($rowIdx + 1) . '">';
            foreach ($row['data'] as $colIdx => $value) {
                $ref = $this->colLetter($colIdx) . ($rowIdx + 1);
                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"' . $style . '>'
                          . '<is><t>' . $this->escape((string) $value) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
             . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
             . '<Default Extension="xml" ContentType="application/xml"/>'
             . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
             . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
             . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
             . '</Types>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
             . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
             . '</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
             . '<sheets><sheet name="Laporan" sheetId="1" r:id="rId1"/></sheets>'
             . '</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
             . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
             . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
             . '</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
             . '<fonts count="2">'
             .   '<font><sz val="11"/><name val="Calibri"/></font>'
             .   '<font><b/><sz val="11"/><name val="Calibri"/></font>'
             . '</fonts>'
             . '<fills count="2">'
             .   '<fill><patternFill patternType="none"/></fill>'
             .   '<fill><patternFill patternType="gray125"/></fill>'
             . '</fills>'
             . '<borders count="1">'
             .   '<border><left/><right/><top/><bottom/><diagonal/></border>'
             . '</borders>'
             . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
             . '<cellXfs count="2">'
             .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
             .   '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
             . '</cellXfs>'
             . '</styleSheet>';
    }
}
