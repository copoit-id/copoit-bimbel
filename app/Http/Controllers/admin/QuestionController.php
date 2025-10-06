<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\TryoutDetail;
use App\Models\Tryout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuestionController extends Controller
{
    public function index($tryout_detail_id)
    {
        try {
            $tryout_detail = TryoutDetail::findOrFail($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();
            $questions = Question::with('questionOptions')->where('tryout_detail_id', $tryout_detail_id)->get();

            return view('admin.pages.question.index', compact('tryout', 'tryout_detail', 'questions'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function create($tryout_detail_id)
    {
        try {
            $tryout_detail = TryoutDetail::findOrFail($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();

            return view('admin.pages.question.create', compact('tryout', 'tryout_detail'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function store(Request $request, $tryout_detail_id)
    {
        try {
            // Validation
            $request->validate([
                'question_text' => 'required|string',
                'option_a' => 'required|string|max:255',
                'option_b' => 'required|string|max:255',
                'option_c' => 'required|string|max:255',
                'option_d' => 'required|string|max:255',
                'option_e' => 'nullable|string|max:255',
                'correct_answer' => 'required|in:A,B,C,D,E',
                'explanation' => 'nullable|string',
                // 'sound' => 'nullable|file|mimes:mp3,mp3a,wav,m4a|max:10120',
                'sound' => 'nullable|file|max:512000',
                'use_custom_scores' => 'nullable|boolean',
                'score_a' => 'nullable|numeric|min:0|max:5',
                'score_b' => 'nullable|numeric|min:0|max:5',
                'score_c' => 'nullable|numeric|min:0|max:5',
                'score_d' => 'nullable|numeric|min:0|max:5',
                'score_e' => 'nullable|numeric|min:0|max:5',
            ]);

            if ($request->correct_answer === 'E' && empty($request->option_e)) {
                return redirect()->back()
                    ->with('error', 'Pilihan E tidak boleh kosong jika dipilih sebagai jawaban benar')
                    ->withInput();
            }

            $tryoutDetail = TryoutDetail::findOrFail($tryout_detail_id);
            $tryout = Tryout::find($tryoutDetail->tryout_id);

            $soundPath = null;
            if ($request->hasFile('sound')) {
                $soundPath = $request->file('sound')->store('questions/audio', 'public');
            }

            // Determine if this is SKD type
            $isSKDType = in_array($tryoutDetail->type_subtest, ['twk', 'tiu', 'tkp']);
            $useCustomScores = $request->use_custom_scores || ($isSKDType && $tryoutDetail->type_subtest === 'tkp');

            $question = Question::create([
                'tryout_detail_id' => $tryout_detail_id,
                'question_type' => 'multiple_choice',
                'question_text' => $request->question_text,
                'sound' => $soundPath,
                'explanation' => $request->explanation,
                'default_weight' => $this->getDefaultWeight($tryoutDetail->type_subtest),
                'custom_score' => $useCustomScores ? 'yes' : 'no',
            ]);

            $options = [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ];

            if (!empty($request->option_e)) {
                $options[] = ['key' => 'E', 'text' => $request->option_e];
            }

            foreach ($options as $option) {
                $isCorrect = ($option['key'] === $request->correct_answer);
                $weight = $this->calculateOptionWeight(
                    $tryoutDetail->type_subtest,
                    $option['key'],
                    $isCorrect,
                    $request,
                    $useCustomScores
                );

                QuestionOption::create([
                    'question_id' => $question->question_id,
                    'option_text' => $option['text'],
                    'weight' => $weight,
                    'is_correct' => $this->determineIsCorrect($tryoutDetail->type_subtest, $isCorrect, $weight),
                ]);
            }

            // Update question default weight based on maximum option weight
            $maxWeight = QuestionOption::where('question_id', $question->question_id)->max('weight');
            $question->update(['default_weight' => $maxWeight]);

            return redirect()->route('admin.question.index', $tryout_detail_id)
                ->with('success', 'Soal berhasil ditambahkan dengan aturan ' . strtoupper($tryoutDetail->type_subtest));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan soal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($tryout_detail_id, $question_id)
    {
        try {
            $tryout_detail = TryoutDetail::findOrFail($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();
            $question = Question::with(['questionOptions' => function ($query) {
                $query->orderBy('question_option_id');
            }])->where('question_id', $question_id)
                ->where('tryout_detail_id', $tryout_detail_id)
                ->firstOrFail();

            return view('admin.pages.question.create', compact('tryout', 'tryout_detail', 'question'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $tryout_detail_id, $question_id)
    {
        try {
            $request->validate([
                'question_text' => 'required|string',
                'option_a' => 'required|string|max:255',
                'option_b' => 'required|string|max:255',
                'option_c' => 'required|string|max:255',
                'option_d' => 'required|string|max:255',
                'option_e' => 'nullable|string|max:255',
                'correct_answer' => 'required|in:A,B,C,D,E',
                'explanation' => 'nullable|string',
                // 'sound' => 'nullable|file|mimes:mp3,wav,m4a|max:5120',
                'use_custom_scores' => 'nullable|boolean',
                'score_a' => 'nullable|numeric|min:0|max:5',
                'score_b' => 'nullable|numeric|min:0|max:5',
                'score_c' => 'nullable|numeric|min:0|max:5',
                'score_d' => 'nullable|numeric|min:0|max:5',
                'score_e' => 'nullable|numeric|min:0|max:5',
            ]);

            if ($request->correct_answer === 'E' && empty($request->option_e)) {
                return redirect()->back()
                    ->with('error', 'Pilihan E tidak boleh kosong jika dipilih sebagai jawaban benar')
                    ->withInput();
            }

            $question = Question::where('question_id', $question_id)->firstOrFail();
            $tryoutDetail = TryoutDetail::findOrFail($tryout_detail_id);

            $soundPath = $question->sound;
            if ($request->hasFile('sound')) {
                $soundPath = $request->file('sound')->store('questions/audio', 'public');
            }

            // Determine if this is SKD type
            $isSKDType = in_array($tryoutDetail->type_subtest, ['twk', 'tiu', 'tkp']);
            $useCustomScores = $request->use_custom_scores || ($isSKDType && $tryoutDetail->type_subtest === 'tkp');

            $question->update([
                'question_text' => $request->question_text,
                'sound' => $soundPath,
                'explanation' => $request->explanation,
                'custom_score' => $useCustomScores ? 'yes' : 'no',
            ]);

            // Delete existing options
            QuestionOption::where('question_id', $question_id)->delete();

            $newOptions = [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ];

            if (!empty($request->option_e)) {
                $newOptions[] = ['key' => 'E', 'text' => $request->option_e];
            }

            foreach ($newOptions as $newOption) {
                $isCorrect = ($newOption['key'] === $request->correct_answer);
                $weight = $this->calculateOptionWeight(
                    $tryoutDetail->type_subtest,
                    $newOption['key'],
                    $isCorrect,
                    $request,
                    $useCustomScores
                );

                QuestionOption::create([
                    'question_id' => $question->question_id,
                    'option_text' => $newOption['text'],
                    'weight' => $weight,
                    'is_correct' => $this->determineIsCorrect($tryoutDetail->type_subtest, $isCorrect, $weight),
                ]);
            }

            $maxWeight = QuestionOption::where('question_id', $question->question_id)->max('weight');
            $question->update(['default_weight' => $maxWeight]);

            return redirect()->route('admin.question.index', $tryout_detail_id)
                ->with('success', 'Soal berhasil diperbarui dengan aturan ' . strtoupper($tryoutDetail->type_subtest));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui soal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($tryout_detail_id, $question_id)
    {
        try {
            $question = Question::where('question_id', $question_id)
                ->where('tryout_detail_id', $tryout_detail_id)
                ->firstOrFail();

            // Delete related question options first
            QuestionOption::where('question_id', $question_id)->delete();

            // Delete audio file if exists
            if ($question->sound && Storage::disk('public')->exists($question->sound)) {
                Storage::disk('public')->delete($question->sound);
            }

            // Delete the question
            $question->delete();

            return redirect()->route('admin.question.index', $tryout_detail_id)
                ->with('success', 'Soal berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus soal: ' . $e->getMessage());
        }
    }

    /**
     * Get default weight based on subtest type
     */
    private function getDefaultWeight($type_subtest)
    {
        switch ($type_subtest) {
            case 'twk':
            case 'tiu':
                return 5.00; // SKD TWK/TIU: 5 poin per soal
            case 'tkp':
                return 5.00; // SKD TKP: maksimal 5 poin per soal
            default:
                return 1.00; // Tryout biasa: 1 poin per soal
        }
    }

    /**
     * Calculate option weight based on subtest type and rules
     */
    private function calculateOptionWeight($type_subtest, $optionKey, $isCorrect, $request, $useCustomScores)
    {
        switch ($type_subtest) {
            case 'twk':
            case 'tiu':
                // SKD TWK/TIU: Benar = 5 poin, Salah = 0 poin
                return $isCorrect ? 5.00 : 0.00;

            case 'tkp':
                // SKD TKP: Semua opsi bisa diberi skor 1-5
                if ($useCustomScores) {
                    $scoreField = 'score_' . strtolower($optionKey);
                    return (float)($request->$scoreField ?? 1);
                }
                // Default TKP: jawaban benar = 5, lainnya = 1
                return $isCorrect ? 5.00 : 1.00;

            default:
                // Tryout biasa
                if ($useCustomScores) {
                    $scoreField = 'score_' . strtolower($optionKey);
                    return (float)($request->$scoreField ?? 0);
                }
                return $isCorrect ? 1.00 : 0.00;
        }
    }

    /**
     * Determine if option is considered "correct" based on subtest type
     */
    private function determineIsCorrect($type_subtest, $isCorrect, $weight)
    {
        switch ($type_subtest) {
            case 'tkp':
                // TKP: Semua opsi dengan weight > 0 dianggap "benar"
                return $weight > 0;
            default:
                // TWK, TIU, dan tryout biasa: hanya jawaban yang benar-benar correct
                return $isCorrect;
        }
    }
}
