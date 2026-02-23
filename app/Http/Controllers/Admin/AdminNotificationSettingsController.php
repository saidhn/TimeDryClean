<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class AdminNotificationSettingsController extends Controller
{
    public function index()
    {
        $templates = NotificationTemplate::orderBy('key')->get();
        return view('admin.notifications.index', compact('templates'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'templates' => 'required|array',
            'templates.*.id' => 'required|exists:notification_templates,id',
            'templates.*.message_ar' => 'nullable|string|max:1000',
            'templates.*.message_en' => 'nullable|string|max:1000',
        ]);

        foreach ($request->templates as $data) {
            NotificationTemplate::where('id', $data['id'])->update([
                'message_ar' => $data['message_ar'] ?? null,
                'message_en' => $data['message_en'] ?? null,
            ]);
        }

        return redirect()->route('admin.notifications.index')->with('success', __('messages.updated_successfully'));
    }
}
