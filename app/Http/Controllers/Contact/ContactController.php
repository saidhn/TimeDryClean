<?php

namespace App\Http\Controllers\Contact;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * Show the contact form.
     *
     * @return \Illuminate\View\View
     */
    public function showForm()
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
    public function index(Request $request)
    {
        $contacts = Contact::with('user');

        // Filter based on user role and deletion status
        if (Auth::user()->user_type === UserType::ADMIN) {
            // Admin: Show messages not deleted by admin
            $contacts->where('deleted_by_admin', false);
        } else {
            // Non-admin: Show messages not deleted by user and belonging to the user
            $contacts->where('user_id', Auth::id())
                ->where('deleted_by_user', false);
        }

        // Apply search filter
        if ($request->has('search')) {
            $search = '%' . $request->search . '%';
            $contacts->where(function ($query) use ($search) {
                $query->where('title', 'like', $search)
                    ->orWhere('message', 'like', $search);
            });
        }

        // Apply is_read filter
        if ($request->filled('is_read')) {
            $contacts->where('isRead', $request->is_read);
        }

        // Apply is_replied filter
        if ($request->filled('is_replied')) {
            $contacts->where('isReplied', $request->is_replied);
        }

        $contacts = $contacts->paginate(10)->appends($request->query());

        return view('contact.index', compact('contacts'));
    }


    public function show(Contact $contact)
    {
        return view('contact.show', compact('contact'));
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
    public function destroy(Contact $contact)
    {
        if (Auth::user()->user_type === UserType::ADMIN) {
            $contact->update(['deleted_by_admin' => true]);
            return back()->with('success', __('messages.deleted_successfully'));
        } else if (Auth::id() === $contact->user_id) {
            $contact->update(['deleted_by_user' => true]);
            return back()->with('success', __('messages.deleted_successfully'));
        }

        return back()->with('error', __('messages.delete_failed'));
    }
}
