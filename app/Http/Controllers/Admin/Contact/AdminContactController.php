<?php

namespace App\Http\Controllers\Admin\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminContactController extends Controller
{
    public function index(Request $request)
    {
        $contacts = Contact::with('user');

        if ($request->has('search')) {
            $search = '%' . $request->search . '%'; // Store search to avoid repeating code.
            $contacts->where(function ($query) use ($search) {
                $query->where('title', 'like', $search)
                    ->orWhere('message', 'like', $search);
            });
        }

        if ($request->filled('is_read')) {
            $contacts->where('isRead', $request->is_read);
        }

        if ($request->filled('is_replied')) {
            $contacts->where('isReplied', $request->is_replied);
        }

        // Log::info($contacts->toSql(), $contacts->getBindings());

        $contacts = $contacts->paginate(10)->appends($request->query());

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
