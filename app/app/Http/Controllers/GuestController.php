<?php

namespace App\Http\Controllers;


class GuestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function interviewAssessment()
    {
        $title = "Form";
        $all_phase = collect(['phase1']);
        return view('form', compact('title', 'all_phase'));
    }
}
