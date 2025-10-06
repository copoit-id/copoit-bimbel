<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\TryoutDetail;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuestionImportController extends Controller
{
    public function downloadTemplate($tryout_detail_id)
    {
        $tryoutDetail = TryoutDetail::findOrFail($tryout_detail_id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'question_text',
            'B1' => 'question_type',
            'C1' => 'option_a_text',
            'D1' => 'option_a_correct',
            'E1' => 'option_a_weight',
            'F1' => 'option_b_text',
            'G1' => 'option_b_correct',
            'H1' => 'option_b_weight',
            'I1' => 'option_c_text',
            'J1' => 'option_c_correct',
            'K1' => 'option_c_weight',
            'L1' => 'option_d_text',
            'M1' => 'option_d_correct',
            'N1' => 'option_d_weight',
            'O1' => 'option_e_text',
            'P1' => 'option_e_correct',
            'Q1' => 'option_e_weight',
            'R1' => 'explanation'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        // Add instructions row
        $instructions = [
            'A2' => 'Tulis pertanyaan di sini',
            'B2' => 'multiple_choice / essay',
            'C2' => 'Teks pilihan A',
            'D2' => '1 (jika benar) / 0 (jika salah)',
            'E2' => 'Bobot nilai (1-5 untuk TKP)',
            'F2' => 'Teks pilihan B',
            'G2' => '1 (jika benar) / 0 (jika salah)',
            'H2' => 'Bobot nilai (1-5 untuk TKP)',
            'I2' => 'Teks pilihan C',
            'J2' => '1 (jika benar) / 0 (jika salah)',
            'K2' => 'Bobot nilai (1-5 untuk TKP)',
            'L2' => 'Teks pilihan D',
            'M2' => '1 (jika benar) / 0 (jika salah)',
            'N2' => 'Bobot nilai (1-5 untuk TKP)',
            'O2' => 'Teks pilihan E',
            'P2' => '1 (jika benar) / 0 (jika salah)',
            'Q2' => 'Bobot nilai (1-5 untuk TKP)',
            'R2' => 'Penjelasan jawaban (opsional)'
        ];

        foreach ($instructions as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setItalic(true);
            $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB('FFE6E6E6');
        }

        // Add sample data
        $sampleData = [
            'A3' => 'Siapa presiden pertama Indonesia?',
            'B3' => 'multiple_choice',
            'C3' => 'Ir. Soekarno',
            'D3' => '1',
            'E3' => '5',
            'F3' => 'Mohammad Hatta',
            'G3' => '0',
            'H3' => '1',
            'I3' => 'Soeharto',
            'J3' => '0',
            'K3' => '1',
            'L3' => 'B.J. Habibie',
            'M3' => '0',
            'N3' => '1',
            'O3' => 'Megawati',
            'P3' => '0',
            'Q3' => '1',
            'R3' => 'Ir. Soekarno adalah presiden pertama Republik Indonesia yang memproklamasikan kemerdekaan pada 17 Agustus 1945'
        ];

        foreach ($sampleData as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Auto-size columns
        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add notes sheet
        $notesSheet = $spreadsheet->createSheet();
        $notesSheet->setTitle('Petunjuk');

        $notes = [
            'A1' => 'PETUNJUK PENGGUNAAN TEMPLATE IMPORT SOAL',
            'A3' => '1. question_text: Tulis soal lengkap dengan konteks',
            'A4' => '2. question_type: Pilih "multiple_choice" atau "essay"',
            'A5' => '3. option_x_text: Isi dengan teks pilihan jawaban',
            'A6' => '4. option_x_correct: Isi dengan 1 jika benar, 0 jika salah',
            'A7' => '5. option_x_weight: Untuk TKP isi bobot 1-5, untuk lainnya isi 1',
            'A8' => '6. explanation: Isi dengan penjelasan jawaban (opsional)',
            'A10' => 'CATATAN PENTING:',
            'A11' => '- Pastikan hanya ada 1 jawaban benar per soal (kecuali TKP)',
            'A12' => '- Untuk TKP, semua pilihan bisa memiliki bobot berbeda',
            'A13' => '- Jangan ubah format header (baris 1)',
            'A14' => '- Hapus baris instruksi (baris 2) sebelum import',
            'A15' => '- Maksimal 100 soal per file',
        ];

        foreach ($notes as $cell => $value) {
            $notesSheet->setCellValue($cell, $value);
            if ($cell === 'A1' || $cell === 'A10') {
                $notesSheet->getStyle($cell)->getFont()->setBold(true)->setSize(14);
            }
        }

        $notesSheet->getColumnDimension('A')->setWidth(80);

        // Set active sheet back to main sheet
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        $filename = 'template_soal_' . $tryoutDetail->type_subtest . '_' . date('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);

        $writer->save($tempFile);

        return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function import(Request $request, $tryout_detail_id)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:2048'
        ]);

        $tryoutDetail = TryoutDetail::findOrFail($tryout_detail_id);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            // Skip header row
            $headers = array_shift($data);

            // Skip instruction row if exists
            if (isset($data[0]) && strpos($data[0][0], 'Tulis pertanyaan') !== false) {
                array_shift($data);
            }

            $importedCount = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                $rowNumber = $index + 3; // Adjust for header and instruction rows

                // Skip empty rows
                if (empty(trim($row[0]))) {
                    continue;
                }

                try {
                    // Validate required fields
                    if (empty(trim($row[0]))) {
                        $errors[] = "Baris {$rowNumber}: Pertanyaan tidak boleh kosong";
                        continue;
                    }

                    if (empty(trim($row[1]))) {
                        $errors[] = "Baris {$rowNumber}: Tipe soal tidak boleh kosong";
                        continue;
                    }

                    // Create question
                    $question = Question::create([
                        'tryout_detail_id' => $tryout_detail_id,
                        'question_text' => trim($row[0]),
                        'question_type' => strtolower(trim($row[1])),
                        'explanation' => !empty(trim($row[17])) ? trim($row[17]) : null,
                        'created_by' => Auth::id()
                    ]);

                    // Create options for multiple choice
                    if (strtolower(trim($row[1])) === 'multiple_choice') {
                        $options = ['A', 'B', 'C', 'D', 'E'];
                        $hasCorrectAnswer = false;

                        for ($i = 0; $i < 5; $i++) {
                            $optionTextIndex = 2 + ($i * 3); // C, F, I, L, O
                            $optionCorrectIndex = 3 + ($i * 3); // D, G, J, M, P
                            $optionWeightIndex = 4 + ($i * 3); // E, H, K, N, Q

                            $optionText = isset($row[$optionTextIndex]) ? trim($row[$optionTextIndex]) : '';

                            if (!empty($optionText)) {
                                $isCorrect = isset($row[$optionCorrectIndex]) && (int)$row[$optionCorrectIndex] === 1;
                                $weight = isset($row[$optionWeightIndex]) ? (int)$row[$optionWeightIndex] : 1;

                                if ($isCorrect) {
                                    $hasCorrectAnswer = true;
                                }

                                QuestionOption::create([
                                    'question_id' => $question->question_id,
                                    'option_key' => $options[$i],
                                    'option_text' => $optionText,
                                    'is_correct' => $isCorrect,
                                    'weight' => $weight
                                ]);
                            }
                        }

                        // Validate that there's at least one correct answer (except for TKP)
                        if (!$hasCorrectAnswer && $tryoutDetail->type_subtest !== 'tkp') {
                            $errors[] = "Baris {$rowNumber}: Harus ada minimal 1 jawaban benar";
                            $question->delete(); // Delete the question if validation fails
                            continue;
                        }
                    }

                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                }
            }

            $message = "Berhasil import {$importedCount} soal";
            if (!empty($errors)) {
                $message .= ". Error: " . implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= " dan " . (count($errors) - 3) . " error lainnya";
                }
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import soal: ' . $e->getMessage());
        }
    }
}
