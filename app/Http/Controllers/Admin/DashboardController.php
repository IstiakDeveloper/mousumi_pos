<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'user' => auth()->user()
        ]);
    }

    public function users()
    {
        return Inertia::render('Admin/Users', [
            'user' => auth()->user()
        ]);
    }
}
