<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\KecermatanColumn;
use App\Models\KecermatanQuestion;
use App\Models\KecermatanRow;
use App\Models\Package;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KecermatanController extends Controller
{
    /**
     * Menampilkan halaman index kolom kecermatan untuk suatu tryout
     */
    public function index($package_id, $tryout_id)
    {
        $package = $package_id === 'standalone' 
            ? (object) ['package_id' => 'standalone', 'name' => 'Manajemen Tryout']
            : Package::where('package_id', $package_id)->firstOrFail();
        
        $tryout = Tryout::with(['kecermatanColumns.rows'])->findOrFail($tryout_id);
        
        return view('admin.pages.kecermatan.index', compact('package', 'tryout'));
    }

    /**
     * Menampilkan form create kolom kecermatan
     */
    public function create($package_id, $tryout_id)
    {
        $package = $package_id === 'standalone'
            ? (object) ['package_id' => 'standalone', 'name' => 'Manajemen Tryout']
            : Package::where('package_id', $package_id)->firstOrFail();
        
        $tryout = Tryout::findOrFail($tryout_id);
        
        return view('admin.pages.kecermatan.create', compact('package', 'tryout'));
    }

    /**
     * Store kolom kecermatan baru dengan generate soal otomatis
     */
    public function store(Request $request, $package_id, $tryout_id)
    {
        $validated = $request->validate([
            'nama_kolom' => 'required|string|max:255',
            'jumlah_soal' => 'required|integer|min:1|max:100',
            'durasi_kolom' => 'required|integer|min:1',
            'tipe_kolom' => 'required|in:huruf,angka,simbol',
            'kolom_data' => 'required|array|size:5',
            'kolom_data.*' => 'required|string|max:10',
            'baris_soal' => 'required|array|min:1',
            'baris_soal.*' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // Ambil data kolom dari input manual user (bukan generate otomatis)
            $kolomData = $validated['kolom_data'];

            // Create column
            $column = KecermatanColumn::create([
                'tryout_id' => $tryout_id,
                'nama_kolom' => $validated['nama_kolom'],
                'jumlah_soal' => $validated['jumlah_soal'],
                'durasi_kolom' => $validated['durasi_kolom'],
                'tipe_kolom' => $validated['tipe_kolom'],
                'kolom_data' => $kolomData,
                'order' => KecermatanColumn::where('tryout_id', $tryout_id)->count(),
            ]);

            // Create rows dan generate soal otomatis
            foreach ($validated['baris_soal'] as $index => $rowText) {
                $row = KecermatanRow::create([
                    'column_id' => $column->column_id,
                    'row_number' => $index + 1,
                    'row_text' => $rowText,
                ]);

                // Generate soal otomatis untuk setiap row
                $this->generateSoalKecermatan($column, $row, $kolomData, $validated['jumlah_soal']);
            }

            DB::commit();

            // Redirect ke halaman kecermatan index (baik standalone maupun package)
            return redirect()->route('admin.kecermatan.index', ['package_id' => $package_id, 'tryout_id' => $tryout_id])
                ->with('success', 'Kolom kecermatan berhasil dibuat dengan ' . $validated['jumlah_soal'] . ' soal per baris.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal membuat kolom kecermatan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Generate data kolom (5 item) berdasarkan tipe
     */
    private function generateKolomData($tipe)
    {
        switch ($tipe) {
            case 'huruf':
                $huruf = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $randomStart = rand(0, 21); // 0-21 agar masih ada 5 huruf
                return str_split(substr($huruf, $randomStart, 5));
            
            case 'angka':
                $randomStart = rand(1, 95); // 1-95 agar masih ada 5 angka
                return array_map('strval', range($randomStart, $randomStart + 4));
            
            case 'simbol':
                $simbols = ['★', '●', '▲', '■', '◆', '♦', '♠', '♣', '♥', '♪', '☀', '☁', '☂', '☃', '✈', '✉', '✂', '✎', '✏', '✐'];
                shuffle($simbols);
                return array_slice($simbols, 0, 5);
            
            default:
                return ['A', 'B', 'C', 'D', 'E'];
        }
    }

    /**
     * Generate soal kecermatan otomatis dari 5 nilai kolom yang diisi manual
     * Setiap soal memiliki 4 pilihan (dari 5 nilai), 1 nilai tidak ditampilkan = jawaban benar
     * 
     * Contoh: Kolom = [4, 5, 1, 2, 3]
     * Soal 1: Tampilkan 4, 5, 1, 2 → Jawaban: 3 (yang tidak ditampilkan)
     * Soal 2: Tampilkan 5, 1, 2, 3 → Jawaban: 4 (yang tidak ditampilkan)
     */
    private function generateSoalKecermatan($column, $row, $kolomData, $jumlahSoal)
    {
        // $kolomData berisi 5 nilai yang diisi manual user, misal: [4, 5, 1, 2, 3]
        
        for ($i = 1; $i <= $jumlahSoal; $i++) {
            // Acak urutan 5 nilai kolom
            $kolomCopy = $kolomData;
            shuffle($kolomCopy);
            
            // Ambil 4 nilai pertama untuk jadi pilihan
            $empatPilihan = array_slice($kolomCopy, 0, 4);
            
            // 1 nilai yang tidak diambil (index ke-4) = jawaban benar
            $missing = $kolomCopy[4];

            // Tentukan posisi jawaban benar (A, B, C, atau D)
            $correctIndex = array_search($missing, $empatPilihan);
            $correctAnswer = ['A', 'B', 'C', 'D'][$correctIndex];

            KecermatanQuestion::create([
                'column_id' => $column->column_id,
                'row_id' => $row->row_id,
                'question_number' => $i,
                'option_a' => $empatPilihan[0],
                'option_b' => $empatPilihan[1],
                'option_c' => $empatPilihan[2],
                'option_d' => $empatPilihan[3],
                'correct_answer' => $correctAnswer,
                'missing_from_column' => $missing,
            ]);
        }
    }

    /**
     * Get tryout_detail_id for redirect
     */
    private function getTryoutDetailId($tryout_id)
    {
        $detail = TryoutDetail::where('tryout_id', $tryout_id)->first();
        return $detail ? $detail->tryout_detail_id : $tryout_id;
    }

    /**
     * Edit kolom kecermatan
     */
    public function edit($package_id, $tryout_id, $column_id)
    {
        $package = $package_id === 'standalone'
            ? (object) ['package_id' => 'standalone', 'name' => 'Manajemen Tryout']
            : Package::where('package_id', $package_id)->firstOrFail();
        
        $tryout = Tryout::findOrFail($tryout_id);
        $column = KecermatanColumn::with(['rows', 'questions'])->findOrFail($column_id);
        
        return view('admin.pages.kecermatan.edit', compact('package', 'tryout', 'column'));
    }

    /**
     * Update kolom kecermatan
     */
    public function update(Request $request, $package_id, $tryout_id, $column_id)
    {
        $column = KecermatanColumn::with('rows')->findOrFail($column_id);
        
        $validated = $request->validate([
            'nama_kolom' => 'required|string|max:255',
            'durasi_kolom' => 'required|integer|min:1',
            'kolom_data' => 'required|array|size:5',
            'kolom_data.*' => 'required|string|max:10',
            'baris_soal' => 'required|array|min:1',
            'baris_soal.*' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // Cek apakah kolom_data berubah
            $oldKolomData = $column->kolom_data;
            $newKolomData = $validated['kolom_data'];
            $kolomDataChanged = $oldKolomData !== $newKolomData;

            // Update column info
            $column->update([
                'nama_kolom' => $validated['nama_kolom'],
                'durasi_kolom' => $validated['durasi_kolom'],
                'kolom_data' => $newKolomData,
            ]);

            // Update rows text
            foreach ($validated['baris_soal'] as $index => $rowText) {
                $row = KecermatanRow::where('column_id', $column_id)
                    ->where('row_number', $index + 1)
                    ->first();
                
                if ($row) {
                    $row->update(['row_text' => $rowText]);
                }
            }

            // Kalau kolom_data berubah, regenerate soal
            if ($kolomDataChanged) {
                // Hapus soal lama
                KecermatanQuestion::where('column_id', $column_id)->delete();
                
                // Generate soal baru dengan kolom_data yang baru
                foreach ($column->rows as $row) {
                    $this->generateSoalKecermatan($column, $row, $newKolomData, $column->jumlah_soal);
                }
            }

            DB::commit();

            // Redirect ke halaman kecermatan index (baik standalone maupun package)
            return redirect()->route('admin.kecermatan.index', ['package_id' => $package_id, 'tryout_id' => $tryout_id])
                ->with('success', 'Kolom kecermatan berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memperbarui kolom kecermatan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete kolom kecermatan
     */
    public function destroy($package_id, $tryout_id, $column_id)
    {
        try {
            $column = KecermatanColumn::findOrFail($column_id);
            $column->delete();

            return redirect()->back()->with('success', 'Kolom kecermatan berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus kolom: ' . $e->getMessage());
        }
    }

    /**
     * Preview soal kecermatan
     */
    public function preview($package_id, $tryout_id, $column_id)
    {
        $package = $package_id === 'standalone'
            ? (object) ['package_id' => 'standalone', 'name' => 'Manajemen Tryout']
            : Package::where('package_id', $package_id)->firstOrFail();
        
        $tryout = Tryout::findOrFail($tryout_id);
        $column = KecermatanColumn::with(['rows.questions'])->findOrFail($column_id);
        
        return view('admin.pages.kecermatan.preview', compact('package', 'tryout', 'column'));
    }

    /**
     * Regenerate soal (generate ulang soal dengan random baru)
     */
    public function regenerateSoal($package_id, $tryout_id, $column_id)
    {
        try {
            DB::beginTransaction();

            $column = KecermatanColumn::with('rows')->findOrFail($column_id);
            
            // Hapus soal lama
            KecermatanQuestion::where('column_id', $column_id)->delete();

            // Generate soal baru
            foreach ($column->rows as $row) {
                $this->generateSoalKecermatan($column, $row, $column->kolom_data, $column->jumlah_soal);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Soal berhasil di-generate ulang.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal regenerate soal: ' . $e->getMessage());
        }
    }
}
