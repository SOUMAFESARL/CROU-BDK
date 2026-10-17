<?php
namespace Tests\Feature;
use App\Http\Controllers\admin\Etudiant\etudiantController;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class AdmissionImportReadingTest extends TestCase
{
    private function invoke(string $method, mixed $value): array
    {
        return (new \ReflectionMethod(etudiantController::class, $method))->invoke(app(etudiantController::class), $value);
    }
    public function test_csv_keeps_header_and_first_student(): void
    {
        $file = UploadedFile::fake()->createWithContent('etudiants.csv', "\xEF\xBB\xBFname;civilite;matricule;telephone1;cite;batiment;campagne;chambre;date_entree;numero_lit\nEtudiant;Monsieur;M01;0700000000;Cite;Batiment;2026-2027;101;12/10/2026;\n");
        $rows = $this->invoke('readImportRows', $file);
        $this->assertCount(1, $rows);
        $row = $this->invoke('normalizeImportRow', $rows[0]);
        $this->assertSame('Etudiant', $row['name']);
        $this->assertSame('2026-10-12', $row['date_entree']);
        $this->assertSame('0700000000', $row['telephone1']);
    }
    public function test_sparse_excel_cells_keep_their_column_positions(): void
    {
        if (!class_exists(\ZipArchive::class)) $this->markTestSkipped('ZIP extension required.');
        $path = tempnam(sys_get_temp_dir(), 'admission');
        try {
            $zip = new \ZipArchive(); $zip->open($path, \ZipArchive::OVERWRITE);
            $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>name</t></is></c><c r="B1" t="inlineStr"><is><t>numero_lit</t></is></c><c r="C1" t="inlineStr"><is><t>date_entree</t></is></c></row><row r="2"><c r="A2" t="inlineStr"><is><t>Etudiant</t></is></c><c r="C2"><v>46307</v></c></row></sheetData></worksheet>');
            $zip->close();
            $rows = $this->invoke('readImportRows', new UploadedFile($path, 'etudiants.xlsx', null, null, true));
            $this->assertSame('', $rows[0]['numero_lit']);
            $this->assertSame('46307', $rows[0]['date_entree']);
            $normalized = $this->invoke('normalizeImportRow', $rows[0]);
            $this->assertSame('2026-10-12', $normalized['date_entree']);
        } finally { unlink($path); }
    }
    public function test_missing_date_is_not_invented_and_invalid_date_is_not_rewritten(): void
    {
        $this->assertNull($this->invoke('normalizeImportRow', ['date_entree' => ''])['date_entree']);
        $this->assertSame('31/02/2026', $this->invoke('normalizeImportRow', ['date_entree' => '31/02/2026'])['date_entree']);
    }
}