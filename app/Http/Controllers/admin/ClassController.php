<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Tentor;
use App\Models\Tryout;
use App\Services\PurchaseAccessDuration;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index()
    {
        $classes = ClassModel::with('tentor')->orderBy('schedule_time', 'desc')->paginate(10);

        return view('admin.pages.class.index', compact('classes'));
    }

    public function create()
    {
        $tentors = Tentor::active()->orderBy('name')->get(['id', 'name', 'expertise']);
        $preOptions = Tryout::where('assessment_type', 'pre_test')->orderBy('name')->get(['tryout_id', 'name']);
        $postOptions = Tryout::where('assessment_type', 'post_test')->orderBy('name')->get(['tryout_id', 'name']);

        return view('admin.pages.class.create', compact('tentors', 'preOptions', 'postOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'schedule_time' => 'required|date',
            'zoom_link' => 'nullable|url',
            'drive_link' => 'nullable|url',
            'tentor_id' => 'nullable|exists:tentors,id',
            'mentor' => 'nullable|string|max:255',
            'status' => 'required|in:upcoming,completed,cancelled',
            'price' => 'nullable|integer|min:0',
            'is_for_sale' => 'nullable|boolean',
            'is_displayed' => 'nullable|boolean',
            'type_price' => 'nullable|in:paid,free_unconditional,free_conditional',
            'conditional_requirement' => 'nullable|string',
            'access_duration_unit' => 'nullable|in:forever,day,week,month,year',
            'access_duration_value' => 'nullable|integer|min:1',
            'pre_test_tryout_id' => 'nullable|exists:tryouts,tryout_id|different:post_test_tryout_id',
            'post_test_tryout_id' => 'nullable|exists:tryouts,tryout_id',
        ]);

        try {
            $tentor = $request->filled('tentor_id') ? Tentor::find($request->integer('tentor_id')) : null;

            $class = ClassModel::create([
                'title' => $request->title,
                'schedule_time' => $request->schedule_time,
                'zoom_link' => $request->zoom_link,
                'drive_link' => $request->drive_link,
                'tentor_id' => $tentor?->id,
                'mentor' => $tentor?->name ?: $request->mentor,
                'status' => $request->status,
                'price' => $request->integer('price'),
                'is_for_sale' => $request->boolean('is_for_sale'),
                'is_displayed' => $request->boolean('is_displayed', true),
                'type_price' => $request->input('type_price', 'paid'),
                'conditional_requirement' => $request->input('conditional_requirement'),
                'access_duration_unit' => PurchaseAccessDuration::normalizedUnit($request->input('access_duration_unit')),
                'access_duration_value' => PurchaseAccessDuration::normalizedValue($request->input('access_duration_unit'), $request->input('access_duration_value')),
            ]);

            $this->syncAssessmentsFromRequest($class, $request);

            return redirect()->route('admin.class-schedules.index', ['tab' => 'zoom'])
                ->with('success', 'Kelas berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan kelas: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $class = ClassModel::with(['tentor', 'assessments'])->findOrFail($id);
            $tentors = Tentor::active()
                ->orWhere('id', $class->tentor_id)
                ->orderBy('name')
                ->get(['id', 'name', 'expertise']);
            $preOptions = Tryout::where('assessment_type', 'pre_test')->orderBy('name')->get(['tryout_id', 'name']);
            $postOptions = Tryout::where('assessment_type', 'post_test')->orderBy('name')->get(['tryout_id', 'name']);
            $preAssignment = $class->assessments->firstWhere('pivot.assessment_type', 'pre_test');
            $postAssignment = $class->assessments->firstWhere('pivot.assessment_type', 'post_test');

            return view('admin.pages.class.edit', compact(
                'class',
                'tentors',
                'preOptions',
                'postOptions',
                'preAssignment',
                'postAssignment'
            ));
        } catch (\Exception $e) {
            return redirect()->route('admin.class-schedules.index', ['tab' => 'zoom'])
                ->with('error', 'Kelas tidak ditemukan');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'schedule_time' => 'required|date',
            'zoom_link' => 'nullable|url',
            'drive_link' => 'nullable|url',
            'tentor_id' => 'nullable|exists:tentors,id',
            'mentor' => 'nullable|string|max:255',
            'status' => 'required|in:upcoming,completed,cancelled',
            'price' => 'nullable|integer|min:0',
            'is_for_sale' => 'nullable|boolean',
            'is_displayed' => 'nullable|boolean',
            'type_price' => 'nullable|in:paid,free_unconditional,free_conditional',
            'conditional_requirement' => 'nullable|string',
            'access_duration_unit' => 'nullable|in:forever,day,week,month,year',
            'access_duration_value' => 'nullable|integer|min:1',
            'pre_test_tryout_id' => 'nullable|exists:tryouts,tryout_id|different:post_test_tryout_id',
            'post_test_tryout_id' => 'nullable|exists:tryouts,tryout_id',
        ]);

        try {
            $class = ClassModel::findOrFail($id);
            $tentor = $request->filled('tentor_id') ? Tentor::find($request->integer('tentor_id')) : null;
            $mentorName = $tentor?->name ?: ($request->has('mentor') ? $request->input('mentor') : $class->mentor);
            $class->update([
                'title' => $request->title,
                'schedule_time' => $request->schedule_time,
                'zoom_link' => $request->zoom_link,
                'drive_link' => $request->drive_link,
                'tentor_id' => $tentor?->id,
                'mentor' => $mentorName,
                'status' => $request->status,
                'price' => $request->integer('price'),
                'is_for_sale' => $request->boolean('is_for_sale'),
                'is_displayed' => $request->boolean('is_displayed', true),
                'type_price' => $request->input('type_price', 'paid'),
                'conditional_requirement' => $request->input('conditional_requirement'),
                'access_duration_unit' => PurchaseAccessDuration::normalizedUnit($request->input('access_duration_unit')),
                'access_duration_value' => PurchaseAccessDuration::normalizedValue($request->input('access_duration_unit'), $request->input('access_duration_value')),
            ]);
            $this->syncAssessmentsFromRequest($class, $request);

            return redirect()->route('admin.class-schedules.index', ['tab' => 'zoom'])
                ->with('success', 'Kelas berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui kelas: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $class = ClassModel::findOrFail($id);
            $class->delete();
            return redirect()->route('admin.class-schedules.index', ['tab' => 'zoom'])
                ->with('success', 'Kelas berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus kelas: ' . $e->getMessage());
        }
    }

    public function assessments($id)
    {
        $class = ClassModel::with('assessments')->findOrFail($id);

        $preAssignment = $class->assessments->firstWhere('pivot.assessment_type', 'pre_test');
        $postAssignment = $class->assessments->firstWhere('pivot.assessment_type', 'post_test');

        $preOptions = Tryout::where('assessment_type', 'pre_test')->orderBy('name')->get();
        $postOptions = Tryout::where('assessment_type', 'post_test')->orderBy('name')->get();

        return view('admin.pages.class.assessments', compact(
            'class',
            'preAssignment',
            'postAssignment',
            'preOptions',
            'postOptions'
        ));
    }

    public function storeAssessment(Request $request, $id)
    {
        $class = ClassModel::findOrFail($id);

        $validated = $request->validate([
            'assessment_type' => 'required|in:pre_test,post_test',
            'tryout_id' => 'required|exists:tryouts,tryout_id',
        ]);

        $tryout = Tryout::where('tryout_id', $validated['tryout_id'])
            ->where('assessment_type', $validated['assessment_type'])
            ->first();

        if (!$tryout) {
            return redirect()->back()->with('error', 'Tryout yang dipilih tidak sesuai dengan kategori penilaian.');
        }

        $class->assessments()->wherePivot('assessment_type', $validated['assessment_type'])->detach();
        $class->assessments()->attach($tryout->tryout_id, ['assessment_type' => $validated['assessment_type']]);

        return redirect()->route('admin.class.assessments', $class->class_id)
            ->with('success', ucfirst(str_replace('_', ' ', $validated['assessment_type'])) . ' berhasil diatur.');
    }

    public function destroyAssessment($id, $assessmentType)
    {
        if (!in_array($assessmentType, ['pre_test', 'post_test'])) {
            abort(404);
        }

        $class = ClassModel::findOrFail($id);
        $class->assessments()->wherePivot('assessment_type', $assessmentType)->detach();

        return redirect()->route('admin.class.assessments', $class->class_id)
            ->with('success', ucfirst(str_replace('_', ' ', $assessmentType)) . ' berhasil dihapus.');
    }

    private function syncAssessmentsFromRequest(ClassModel $class, Request $request): void
    {
        foreach ([
            'pre_test' => $request->input('pre_test_tryout_id'),
            'post_test' => $request->input('post_test_tryout_id'),
        ] as $assessmentType => $tryoutId) {
            $class->assessments()->wherePivot('assessment_type', $assessmentType)->detach();

            if (!$tryoutId) {
                continue;
            }

            $tryout = Tryout::where('tryout_id', $tryoutId)
                ->where('assessment_type', $assessmentType)
                ->first();

            if ($tryout) {
                $class->assessments()->attach($tryout->tryout_id, ['assessment_type' => $assessmentType]);
            }
        }
    }

}
