<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientSettingsController extends Controller
{
    public function index()
    {
        $client = auth()->user();
        return view('client.settings.index', compact('client'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'notification_language' => 'required|in:ar,en',
        ]);

        auth()->user()->update([
            'notification_language' => $request->notification_language,
        ]);

        return redirect()->route('client.settings')->with('success', __('messages.settings_updated'));
    }
}
