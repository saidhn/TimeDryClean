<?php

namespace App\Http\Controllers\Admin\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class AdminContactController extends Controller
{
    public function index()
    {
        $contacts = Contact::with('user')->paginate(10); // Adjust pagination as needed
        return view('admin.contacts.index', compact('contacts'));
    }

    public function show(Contact $contact)
    {
        return view('admin.contacts.show', compact('contact'));
    }

    public function markRead(Contact $contact)
    {
        $contact->isRead = !$contact->isRead;
        $contact->save();
        return back()->with('success', __('messages.contact_status_updated'));
    }

    public function markReplied(Contact $contact)
    {
        $contact->isReplied = !$contact->isReplied;
        $contact->save();
        return back()->with('success', __('messages.contact_status_updated'));
    }
    public function reply(Request $request, Contact $contact)
    {
        $request->validate([
            'reply_message' => 'required|string',
        ]);

        $replies = $contact->replies ?? [];
        $replies[] = [
            'message' => $request->reply_message,
            'created_at' => now()->toDateTimeString(),
        ];

        $contact->replies = $replies;
        $contact->isReplied = true;
        $contact->save();

        return back()->with('success', __('messages.reply_sent'));
    }
}
