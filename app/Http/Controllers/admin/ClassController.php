<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
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
}
