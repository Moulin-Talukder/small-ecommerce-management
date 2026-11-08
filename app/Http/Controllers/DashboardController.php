<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Generic /dashboard entry point: sends each user to their role's area.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->role->homeRoute());
    }
}
