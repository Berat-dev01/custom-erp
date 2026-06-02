<?php

namespace App\Erp\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('erp.dashboard.view');

        return view('erp::admin.dashboard.index');
    }
}
