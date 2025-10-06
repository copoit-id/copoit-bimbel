<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CertificationController extends Controller
{
    public function index()
    {
        return view('admin.pages.certification.index');
    }

    public function create()
    {
        // Return create view if needed
    }

    public function store(Request $request)
    {
        // Store new certification
    }

    public function edit($id)
    {
        // Return edit view if needed
    }

    public function update(Request $request, $id)
    {
        // Update certification
    }

    public function destroy($id)
    {
        // Delete certification
    }
}
