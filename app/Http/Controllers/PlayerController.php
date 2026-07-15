<?php

namespace App\Http\Controllers;

use App\Models\EventState;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function register()
    {
        return view('player.register');
    }

    public function store(Request $request)
    {
        // Registration is handled via the Vue component → /api/players
        // This route exists only as a fallback for non-JS clients
        return redirect()->route('player.play');
    }

    public function play(Request $request)
    {
        $adminPreview = $request->boolean('admin_preview')
            && $request->session()->get('admin_logged_in') === true;

        return view('player.play', compact('adminPreview'));
    }
}
