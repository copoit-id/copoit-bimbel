<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Tryout;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index()
    {
        $classes = ClassModel::orderBy('schedule_time', 'desc')->paginate(10);
        return view('admin.pages.class.index', compact('classes'));
    }

    public function create()
    {
        return view('admin.pages.class.create');
    }

    public function store(Request $request)
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
            ClassModel::create([
                'title' => $request->title,
                'schedule_time' => $request->schedule_time,
                'zoom_link' => $request->zoom_link,
                'drive_link' => $request->drive_link,
                'mentor' => $request->mentor,
                'status' => $request->status
            ]);

            return redirect()->route('admin.class.index')
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
            $class = ClassModel::findOrFail($id);
            return view('admin.pages.class.edit', compact('class'));
        } catch (\Exception $e) {
            return redirect()->route('admin.class.index')
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
            'mentor' => 'nullable|string|max:255',
            'status' => 'required|in:upcoming,completed,cancelled',
        ]);

        try {
            $class = ClassModel::findOrFail($id);
            $class->update([
                'title' => $request->title,
                'schedule_time' => $request->schedule_time,
                'zoom_link' => $request->zoom_link,
                'drive_link' => $request->drive_link,
                'mentor' => $request->mentor,
                'status' => $request->status
            ]);

            return redirect()->route('admin.class.index')
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
            return redirect()->route('admin.class.index')
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
}
