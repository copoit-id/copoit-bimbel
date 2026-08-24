<?php

namespace Tests\Unit;

use App\Http\Controllers\admin\ParticipantDestinationCategoryController;
use App\Models\ParticipantDestinationCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ParticipantDestinationCategoryImportTest extends TestCase
{
    private string $spreadsheetPath;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('participant_destination_categories');
        Schema::create('participant_destination_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['parent_id', 'slug']);
        });

        $this->spreadsheetPath = tempnam(sys_get_temp_dir(), 'destination-import-');
    }

    protected function tearDown(): void
    {
        @unlink($this->spreadsheetPath);
        Schema::dropIfExists('participant_destination_categories');

        parent::tearDown();
    }

    public function test_it_imports_institutions_and_programs_without_duplicate_categories(): void
    {
        $this->writeSpreadsheet([
            ['Universitas', 'Jurusan', 'Status', 'Urutan'],
            ['Universitas Indonesia', 'Ilmu Komputer', 'aktif', 1],
            ['Universitas Indonesia', 'Sistem Informasi', 'aktif', 2],
            ['Institut Teknologi Bandung', 'Teknik Informatika', 'nonaktif', 3],
        ]);

        $this->importSpreadsheet();
        $this->importSpreadsheet();

        $this->assertSame(2, ParticipantDestinationCategory::query()->root()->count());

        $ui = ParticipantDestinationCategory::query()
            ->root()
            ->where('name', 'Universitas Indonesia')
            ->firstOrFail();
        $itb = ParticipantDestinationCategory::query()
            ->root()
            ->where('name', 'Institut Teknologi Bandung')
            ->firstOrFail();

        $this->assertSame(2, $ui->children()->count());
        $this->assertSame(1, $itb->children()->count());
        $this->assertFalse($itb->children()->firstOrFail()->is_active);
        $this->assertSame(3, $itb->children()->firstOrFail()->sort_order);
    }

    public function test_it_skips_existing_destinations_case_insensitively(): void
    {
        $this->writeSpreadsheet([
            ['Universitas', 'Jurusan', 'Status', 'Urutan'],
            ['Universitas Indonesia', 'Ilmu Komputer', 'aktif', 1],
        ]);

        $this->importSpreadsheet();

        $this->writeSpreadsheet([
            ['Universitas', 'Jurusan', 'Status', 'Urutan'],
            ['UNIVERSITAS INDONESIA', 'ILMU KOMPUTER', 'nonaktif', 9],
            ['universitas indonesia', 'Sistem Informasi', 'aktif', 2],
            ['Universitas Indonesia', 'sistem informasi', 'aktif', 3],
        ]);

        $this->importSpreadsheet();

        $university = ParticipantDestinationCategory::query()
            ->root()
            ->where('name', 'Universitas Indonesia')
            ->firstOrFail();

        $this->assertSame(1, ParticipantDestinationCategory::query()->root()->count());
        $this->assertSame(2, $university->children()->count());
        $this->assertSame(1, $university->children()->where('name', 'Ilmu Komputer')->count());
        $this->assertSame(1, $university->children()->where('name', 'Sistem Informasi')->count());
        $this->assertTrue($university->children()->where('name', 'Ilmu Komputer')->firstOrFail()->is_active);
        $this->assertSame(1, $university->children()->where('name', 'Ilmu Komputer')->firstOrFail()->sort_order);
    }

    private function writeSpreadsheet(array $rows): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        (new Xlsx($spreadsheet))->save($this->spreadsheetPath);
    }

    private function importSpreadsheet(): void
    {
        $request = Request::create('/', 'POST', [], [], [
            'excel_file' => new UploadedFile(
                $this->spreadsheetPath,
                'tujuan.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
        ]);

        app(ParticipantDestinationCategoryController::class)->import($request);
    }
}
