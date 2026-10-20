<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Génère un classeur Excel (.xlsx) d'une seule feuille sans dépendance externe
 * (ZipArchive) : ligne d'en-tête en gras et figée, cellules texte ; relit aussi un .xlsx ou un .csv importé.
 */
class SimpleXlsx
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public static function download(string $filename, string $sheetName, array $headers, iterable $rows): BinaryFileResponse
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException("L'export Excel n'est pas disponible sur ce serveur (extension zip manquante).");
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.self::escape(mb_substr($sheetName, 0, 31)).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        // Style 0 : normal ; style 1 : en-tête en gras sur fond vert clair (charte).
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEBF8DE"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf fontId="0" fillId="0" borderId="0"/><xf fontId="1" fillId="2" borderId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');

        $sheetRows = self::row(1, $headers, 1);
        $rowNumber = 1;
        foreach ($rows as $row) {
            $sheetRows .= self::row(++$rowNumber, array_values($row));
        }

        $cols = '';
        foreach ($headers as $index => $header) {
            $cols .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.max(14, mb_strlen($header) + 4).'" customWidth="1"/>';
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>'.$cols.'</cols><sheetData>'.$sheetRows.'</sheetData></worksheet>');
        $zip->close();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Lit la première feuille d'un .xlsx ou un .csv (séparateur ; ou ,) : une ligne par enregistrement,
     * indexée par les en-têtes de la première ligne ; les lignes vides sont ignorées.
     *
     * @return list<array<string, string>>
     */
    public static function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['csv', 'txt'], true)) {
            $content = (string) file_get_contents($file->getRealPath());
            // Excel (Windows FR) enregistre souvent les CSV en Windows-1252 : on convertit pour garder les accents (Cité, Bâtiment…).
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
            }
            $handle = fopen('php://temp', 'w+b');
            fwrite($handle, $content);
            rewind($handle);
            $delimiter = str_contains((string) fgets($handle), ';') ? ';' : ',';
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter) ?: [];
            $rows = [];
            while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) continue;
                $rows[] = array_combine(array_pad($headers, count($values), ''), array_pad($values, count($headers), '')) ?: [];
            }
            fclose($handle);
            return $rows;
        }

        if ($extension !== 'xlsx' || !class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['fichier' => 'Format non supporté. Utilisez un fichier .xlsx ou .csv.']);
        }
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) throw ValidationException::withMessages(['fichier' => 'Impossible de lire le fichier Excel.']);
        // Texte d'une chaîne simple (<t>) ou enrichie (<r><t>…</t></r>…), partagée ou en ligne.
        $text = function (?\SimpleXMLElement $node): string {
            if ($node === null) return '';
            if (isset($node->t)) return (string) $node->t;
            $value = '';
            foreach ($node->r as $run) $value .= (string) $run->t;
            return $value;
        };
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false && ($sharedXml = simplexml_load_string($xml)) !== false) {
            foreach ($sharedXml->si as $item) $shared[] = $text($item);
        }
        $sheetXml = $zip->getFromName(self::firstWorksheetPath($zip));
        $sheet = $sheetXml !== false ? simplexml_load_string($sheetXml) : false;
        if ($sheet === false || !isset($sheet->sheetData)) {
            $zip->close();
            throw ValidationException::withMessages(['fichier' => 'Impossible de lire la première feuille du fichier Excel.']);
        }
        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $values = [];
            $column = 0;
            foreach ($row->c as $cell) {
                // L'attribut r (ex. « C2 ») est facultatif : à défaut, la cellule suit la précédente.
                if (preg_match('/^([A-Z]+)/', (string) $cell['r'], $match)) {
                    $column = 0;
                    foreach (str_split($match[1]) as $letter) $column = $column * 26 + ord($letter) - 64;
                } else {
                    $column++;
                }
                $type = (string) $cell['t'];
                $value = match ($type) {
                    'inlineStr' => $text($cell->is),
                    's' => $shared[(int) $cell->v] ?? '',
                    default => (string) ($cell->v ?? ''),
                };
                $values[$column - 1] = $value;
            }
            $rows[] = $values;
        }
        $zip->close();
        $headers = array_shift($rows) ?: [];
        if (!$headers) return [];
        $width = max(array_keys($headers)) + 1;
        $headerValues = [];
        for ($index = 0; $index < $width; $index++) $headerValues[] = $headers[$index] ?? '';
        $result = [];
        foreach ($rows as $row) {
            if (!array_filter($row, fn ($value) => trim((string) $value) !== '')) continue;
            $values = [];
            for ($index = 0; $index < $width; $index++) $values[] = $row[$index] ?? '';
            $result[] = array_combine($headerValues, $values);
        }
        return $result;
    }

    /** Chemin de la première feuille du classeur (pas forcément sheet1.xml une fois le fichier réenregistré). */
    private static function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook !== false && $rels !== false) {
            $workbookXml = simplexml_load_string($workbook);
            $relsXml = simplexml_load_string($rels);
            $first = $workbookXml?->sheets?->sheet[0] ?? null;
            $id = $first ? (string) ($first->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '') : '';
            foreach ($relsXml?->Relationship ?? [] as $relation) {
                if ((string) $relation['Id'] === $id) {
                    $target = ltrim((string) $relation['Target'], '/');
                    return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                }
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    private static function row(int $number, array $values, int $style = 0): string
    {
        $xml = '<row r="'.$number.'">';
        foreach ($values as $index => $value) {
            $ref = self::column($index + 1).$number;
            $styleAttr = $style ? ' s="'.$style.'"' : '';
            $xml .= is_int($value) || is_float($value)
                ? '<c r="'.$ref.'"'.$styleAttr.'><v>'.$value.'</v></c>'
                : '<c r="'.$ref.'"'.$styleAttr.' t="inlineStr"><is><t xml:space="preserve">'.self::escape((string) $value).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private static function column(int $number): string
    {
        $letters = '';
        while ($number > 0) {
            $number--;
            $letters = chr(65 + ($number % 26)).$letters;
            $number = intdiv($number, 26);
        }

        return $letters;
    }

    private static function escape(string $value): string
    {
        // Retire les caractères de contrôle interdits en XML.
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
