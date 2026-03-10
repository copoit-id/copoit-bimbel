<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\UpdateNotification;
use Illuminate\Http\Request;

class UpdateNotificationController extends Controller
{
    public function index()
    {
        $updates = UpdateNotification::query()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('admin.pages.update-notifications.index', compact('updates'));
    }

    public function show(UpdateNotification $updateNotification)
    {
        return view('admin.pages.update-notifications.show', [
            'update' => $updateNotification,
        ]);
    }
}
