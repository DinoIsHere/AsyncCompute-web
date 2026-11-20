<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    /**
     * Show the public about page for PT Asyncindo.
     */
    public function index(): Response
    {
        return Inertia::render('Public/About');
    }
}
