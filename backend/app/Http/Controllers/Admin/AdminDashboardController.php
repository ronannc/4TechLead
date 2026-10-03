<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard/presentation/pages/Index');
    }
}
