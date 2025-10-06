<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DiscussionController extends Controller
{
    public function index()
    {
        return view('admin.pages.discussion.index');
    }

    public function create()
    {
        // Return create view if needed
    }

    public function store(Request $request)
    {
        // Store new discussion
    }

    public function edit($id)
    {
        // Return edit view if needed
    }

    public function update(Request $request, $id)
    {
        // Update discussion
    }

    public function destroy($id)
    {
        // Delete discussion
    }
}
