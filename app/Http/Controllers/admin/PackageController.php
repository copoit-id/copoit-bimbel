<?php

namespace App\Http\Controllers\admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Requests\PackageRequest;
use App\Services\PackageService;
use App\Models\ClassModel;
use App\Models\Package;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\DetailPackage;
use Illuminate\Http\Request;

Carbon::setLocale('id');
class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::all();
        return view('admin.pages.package.index', compact('packages'));
    }

    public function create()
    {
        $classes = ClassModel::all();
        return view('admin.pages.package.create', compact('classes'));
    }

    public function store(Request $request)
    {
        try {
            $validationRules = [
                'name' => 'required|string|max:255',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'type_package' => 'required|in:bimbel,tryout,sertifikasi',
                'type_price' => 'required|in:free,paid',
                'status' => 'required|in:active,inactive',
                'description' => 'nullable|string',
                'features' => 'nullable|array',
            ];

            // Add price validation only if type_price is 'paid'
            if ($request->type_price === 'paid') {
                $validationRules['price'] = 'required|integer|min:1';
            } else {
                $validationRules['price'] = 'nullable|integer|min:0';
            }

            $validated = $request->validate($validationRules);

            // Set price to 0 if type is free
            if ($request->type_price === 'free') {
                $validated['price'] = 0;
            }

            $validated['features'] = isset($validated['features']) && is_array($validated['features'])
                ? json_encode($validated['features'])
                : null;

            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('packages', 'public');
            }

            Package::create($validated);
            return redirect()->route('admin.package.index')->with('success', 'Paket berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan paket: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $package = Package::findOrFail($id);
            $classes = ClassModel::all();
            return view('admin.pages.package.create', compact('package', 'classes'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Paket tidak ditemukan');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $package = Package::findOrFail($id);

            $validationRules = [
                'name' => 'required|string|max:255',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'type_package' => 'required|in:bimbel,tryout,sertifikasi',
                'type_price' => 'required|in:free,paid',
                'status' => 'required|in:active,inactive',
                'description' => 'nullable|string',
                'features' => 'nullable|array',
            ];

            // Add price validation only if type_price is 'paid'
            if ($request->type_price === 'paid') {
                $validationRules['price'] = 'required|integer|min:1';
            } else {
                $validationRules['price'] = 'nullable|integer|min:0';
            }

            $validated = $request->validate($validationRules);

            // Set price to 0 if type is free
            if ($request->type_price === 'free') {
                $validated['price'] = 0;
            }

            $validated['features'] = isset($validated['features']) && is_array($validated['features'])
                ? json_encode($validated['features'])
                : null;

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($package->image && \Storage::disk('public')->exists($package->image)) {
                    \Storage::disk('public')->delete($package->image);
                }
                $validated['image'] = $request->file('image')->store('packages', 'public');
            }

            $package->update($validated);

            return redirect()->route('admin.package.index')
                ->with('success', 'Paket berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui paket: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $package = Package::findOrFail($id);
            $package->delete();
            return redirect()->route('admin.package.index')
                ->with('success', 'Paket berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus paket: ' . $e->getMessage());
        }
    }

    public function indexClass($package_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();

            // Get all classes with their package relationship status
            $classes = ClassModel::with(['detailPackages' => function ($query) use ($package_id) {
                $query->where('package_id', $package_id);
            }])
                ->orderByRaw("(SELECT COUNT(*) FROM detail_packages WHERE detailable_type = ? AND detailable_id = classes.class_id AND package_id = ?) DESC", [ClassModel::class, $package_id])
                ->orderBy('schedule_time', 'desc')
                ->paginate(10);

            return view('admin.pages.package.class.index', compact('package', 'classes'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function createClass($package_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->first();

            return view('admin.pages.package.class.create', compact('package'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function storeClass(Request $request, $package_id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'schedule_time' => 'required|date',
            'zoom_link' => 'nullable|url',
            'drive_link' => 'nullable|url',
            'mentor' => 'nullable|string|max:255',
            'status' => 'required|in:upcoming,completed,cancelled',
        ]);

        try {
            $class = ClassModel::create([
                'package_id' => $package_id,
                'title' => $request->title,
                'schedule_time' => $request->schedule_time,
                'zoom_link' => $request->zoom_link,
                'drive_link' => $request->drive_link,
                'mentor' => $request->mentor,
                'status' => $request->status
            ]);

            return redirect()->route('admin.package.class.index', $package_id)
                ->with('success', 'Kelas "' . $class->name . '" berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan kelas: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function indexTryout($package_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();

            // Get all tryouts with their package relationship status
            $tryouts = Tryout::with(['tryoutDetails.questions', 'detailPackages' => function ($query) use ($package_id) {
                $query->where('package_id', $package_id);
            }])
                ->orderByRaw("(SELECT COUNT(*) FROM detail_packages WHERE detailable_type = ? AND detailable_id = tryouts.tryout_id AND package_id = ?) DESC", [Tryout::class, $package_id])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return view('admin.pages.package.tryout.index', compact('package', 'tryouts'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Paket tidak ditemukan');
        }
    }

    public function createTryout($packageId)
    {
        try {
            $package = Package::where('package_id', $packageId)->firstOrFail();
            return view('admin.pages.package.tryout.create', compact('package'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Paket tidak ditemukan');
        }
    }
    public function storeTryout(Request $request, $package_id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type_tryout' => 'required|in:tiu,twk,tkp,skd_full,general,certification',
            'duration_total' => 'required|integer|min:1',
            'passing_score_total' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_certification' => 'boolean',
            'is_active' => 'boolean',
            'is_toefl' => 'boolean',
            'order' => 'nullable|integer|min:0'
        ]);

        // Buat tryout baru
        $tryout = Tryout::create([
            'package_id' => $package_id,
            'name' => $request->name,
            'description' => $request->description,
            'type_tryout' => $request->type_tryout,
            'is_certification' => $request->has('is_certification'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->has('is_active'),
            'is_toefl' => $request->has('is_toefl')
        ]);

        if ($tryout && $tryout->type_tryout == 'skd_full') {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'twk',
                'duration' => $request->duration_twk,
                'passing_score' => $request->passing_score_twk,
            ]);

            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'tiu',
                'duration' => $request->duration_tiu,
                'passing_score' => $request->passing_score_tiu,
            ]);

            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'tkp',
                'duration' => $request->duration_tkp,
                'passing_score' => $request->passing_score_tkp,
            ]);
        } else if ($tryout && $tryout->type_tryout == 'certification') {
            // Create certification subtests: writing, reading, listening
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'writing',
                'duration' => $request->duration_writing ?? 60,
                'passing_score' => $request->passing_score_writing ?? 60,
            ]);

            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'reading',
                'duration' => $request->duration_reading ?? 60,
                'passing_score' => $request->passing_score_reading ?? 60,
            ]);

            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'listening',
                'duration' => $request->duration_listening ?? 60,
                'passing_score' => $request->passing_score_listening ?? 60,
            ]);
        } else if ($tryout && $tryout->type_tryout == 'twk') {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'twk',
                'duration' => $request->duration_twk,
                'passing_score' => $request->passing_score_twk,
            ]);
        } else if ($tryout && $tryout->type_tryout == 'tiu') {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'tiu',
                'duration' => $request->duration_tiu,
                'passing_score' => $request->passing_score_tiu,
            ]);
        } else if ($tryout && $tryout->type_tryout == 'tkp') {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'tkp',
                'duration' => $request->duration_tkp,
                'passing_score' => $request->passing_score_tkp,
            ]);
        } else if ($tryout && $tryout->type_tryout == 'general') {
            TryoutDetail::create([
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => 'general',
                'duration' => $request->duration_general,
                'passing_score' => $request->passing_score_general,
            ]);
        }

        return redirect()->route('admin.package.tryout.index', $package_id)
            ->with('success', 'Tryout "' . $tryout->name . '" berhasil ditambahkan');
    }

    public function indexSoal($package_id, $tryout_detail_id)
    {
        try {
            // Handle standalone mode (dari manajemen tryout langsung)
            if ($package_id === 'standalone') {
                $package = (object) ['package_id' => 'standalone', 'name' => 'Manajemen Tryout'];
            } else {
                $package = Package::where('package_id', $package_id)->firstOrFail();
            }

            $tryout_detail = TryoutDetail::find($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();
            $questions = Question::with('questionOptions')->where('tryout_detail_id', $tryout_detail_id)->get();

            return view('admin.pages.package.tryout.soal', compact('package', 'tryout', 'questions'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function createSoal($package_id, $tryout_detail_id)
    {
        try {
            // Handle standalone mode
            if ($package_id === 'standalone') {
                $package = (object) ['package_id' => 'standalone', 'name' => 'Manajemen Tryout'];
            } else {
                $package = Package::where('package_id', $package_id)->firstOrFail();
            }

            $tryout_detail = TryoutDetail::find($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();

            return view('admin.pages.package.tryout.create-soal', compact('package', 'tryout'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }
    public function editSoal($package_id, $tryout_detail_id, $question_id)
    {
        try {
            $package = Package::where('package_id', $package_id)->firstOrFail();
            $tryout_detail = TryoutDetail::find($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();
            $question = Question::with('questionOptions')->where('tryout_detail_id', $tryout_detail_id)->where('question_id', $question_id)->first();

            return view('admin.pages.package.tryout.create-soal', compact('package', 'tryout', 'question'));
        } catch (\Exception $e) {
            return redirect()->route('admin.package.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function storeSoal(Request $request, $package_id, $tryout_detail_id)
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
                'sound' => 'nullable|file|mimes:mp3,wav,m4a|max:5120',
                'use_custom_scores' => 'nullable|boolean',
                'score_a' => 'nullable|numeric|min:0',
                'score_b' => 'nullable|numeric|min:0',
                'score_c' => 'nullable|numeric|min:0',
                'score_d' => 'nullable|numeric|min:0',
                'score_e' => 'nullable|numeric|min:0',
            ]);

            if ($request->correct_answer === 'E' && empty($request->option_e)) {
                return redirect()->back()
                    ->with('error', 'Pilihan E tidak boleh kosong jika dipilih sebagai jawaban benar')
                    ->withInput();
            }

            $tryoutDetail = TryoutDetail::findOrFail($tryout_detail_id);

            $soundPath = null;
            if ($request->hasFile('sound')) {
                $soundPath = $request->file('sound')->store('questions/audio', 'public');
            }

            $question = Question::create([
                'tryout_detail_id' => $tryout_detail_id,
                'question_type' => 'multiple_choice',
                'question_text' => $request->question_text,
                'sound' => $soundPath,
                'explanation' => $request->explanation,
                'default_weight' => 1.00,
                'custom_score' => $request->use_custom_scores ? 'yes' : 'no',
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

            foreach ($options as $index => $option) {
                $isCorrect = ($option['key'] === $request->correct_answer);

                $weight = 0;

                if ($request->use_custom_scores) {
                    $scoreField = 'score_' . strtolower($option['key']);
                    $weight = (float)($request->$scoreField ?? 0);
                } else {
                    $weight = $isCorrect ? 1.00 : 0.00;
                }

                QuestionOption::create([
                    'question_id' => $question->question_id,
                    'option_text' => $option['text'],
                    'weight' => $weight,
                    'is_correct' => $isCorrect,
                ]);
            }

            $search_max_weight = QuestionOption::where('question_id', $question->question_id)->max('weight');
            Question::where('question_id', $question->question_id)->update(['default_weight' => $search_max_weight]);

            return redirect()->route('admin.package.tryout.soal', [$package_id, $tryout_detail_id])
                ->with('success', 'Soal berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan soal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function updateSoal(Request $request, $package_id, $tryout_detail_id, $question_id)
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
                'sound' => 'nullable|file|mimes:mp3,wav,m4a|max:5120',
                'use_custom_scores' => 'nullable|boolean',
                'score_a' => 'nullable|numeric|min:0',
                'score_b' => 'nullable|numeric|min:0',
                'score_c' => 'nullable|numeric|min:0',
                'score_d' => 'nullable|numeric|min:0',
                'score_e' => 'nullable|numeric|min:0',
            ]);

            if ($request->correct_answer === 'E' && empty($request->option_e)) {
                return redirect()->back()
                    ->with('error', 'Pilihan E tidak boleh kosong jika dipilih sebagai jawaban benar')
                    ->withInput();
            }

            $question = Question::where('question_id', $question_id)->firstOrFail();

            $soundPath = $question->sound;
            if ($request->hasFile('sound')) {
                $soundPath = $request->file('sound')->store('questions/audio', 'public');
            }

            $question->update([
                'question_text' => $request->question_text,
                'sound' => $soundPath,
                'explanation' => $request->explanation,
                'custom_score' => $request->use_custom_scores ? 'yes' : 'no',
            ]);

            $existingOptions = QuestionOption::where('question_id', $question_id)
                ->orderBy('question_option_id')
                ->get();

            $newOptions = [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ];

            if (!empty($request->option_e)) {
                $newOptions[] = ['key' => 'E', 'text' => $request->option_e];
            }

            foreach ($newOptions as $index => $newOption) {
                $isCorrect = ($newOption['key'] === $request->correct_answer);

                $weight = 0;
                if ($request->use_custom_scores) {
                    $scoreField = 'score_' . strtolower($newOption['key']);
                    $weight = (float)($request->$scoreField ?? 0);
                } else {
                    $weight = $isCorrect ? 1.00 : 0.00;
                }

                if (isset($existingOptions[$index])) {
                    $existingOptions[$index]->update([
                        'option_text' => $newOption['text'],
                        'weight' => $weight,
                        'is_correct' => $isCorrect,
                    ]);
                } else {
                    QuestionOption::create([
                        'question_id' => $question->question_id,
                        'option_text' => $newOption['text'],
                        'weight' => $weight,
                        'is_correct' => $isCorrect,
                    ]);
                }
            }

            if ($existingOptions->count() > count($newOptions)) {
                $optionsToDelete = $existingOptions->slice(count($newOptions));
                foreach ($optionsToDelete as $optionToDelete) {
                    $optionToDelete->delete();
                }
            }

            $maxWeight = QuestionOption::where('question_id', $question->question_id)->max('weight');
            $question->update(['default_weight' => $maxWeight]);

            return redirect()->route('admin.package.tryout.soal', [$package_id, $tryout_detail_id])
                ->with('success', 'Soal berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui soal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function toggleClass(Request $request, $package_id, $class_id)
    {
        try {
            $package = Package::findOrFail($package_id);
            $class = ClassModel::findOrFail($class_id);

            $detailPackage = DetailPackage::where([
                'package_id' => $package_id,
                'detailable_type' => ClassModel::class,
                'detailable_id' => $class_id
            ])->first();

            if ($detailPackage) {
                // Remove from package
                $detailPackage->delete();
                $message = 'Kelas berhasil dihapus dari paket';
            } else {
                // Add to package
                DetailPackage::create([
                    'package_id' => $package_id,
                    'detailable_type' => ClassModel::class,
                    'detailable_id' => $class_id,
                    'order' => 0
                ]);
                $message = 'Kelas berhasil ditambahkan ke paket';
            }

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function toggleTryout(Request $request, $package_id, $tryout_id)
    {
        try {
            $package = Package::findOrFail($package_id);
            $tryout = Tryout::findOrFail($tryout_id);

            $detailPackage = DetailPackage::where([
                'package_id' => $package_id,
                'detailable_type' => Tryout::class,
                'detailable_id' => $tryout_id
            ])->first();

            if ($detailPackage) {
                // Remove from package
                $detailPackage->delete();
                $message = 'Tryout berhasil dihapus dari paket';
            } else {
                // Add to package
                DetailPackage::create([
                    'package_id' => $package_id,
                    'detailable_type' => Tryout::class,
                    'detailable_id' => $tryout_id,
                    'order' => 0
                ]);
                $message = 'Tryout berhasil ditambahkan ke paket';
            }

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
