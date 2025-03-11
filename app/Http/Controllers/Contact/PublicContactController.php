<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\Contact;

class PublicContactController extends Controller
{
    /**
     * Show the contact form.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('contact.sendMessage'); // Ensure you have a view named 'contact.blade.php'
    }

    /**
     * Handle the contact form submission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        Contact::create([
            'title' => $request->title,
            'message' => $request->message,
            'user_id' => auth()->id(), // Optional: if you want to save the user ID
            'date' => now(),
        ]);

        return back()->with('success', __('messages.contact_sent'));
    }
}