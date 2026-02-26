<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->user()->notifications()->latest();

        if ($request->filled('type')) {
            $q->where('data-type', $request->string('type'));
        }

        if ($request->filled("unread")) {
            $unread = filter_var($request->input('unread'), FILTER_VALIDATE_BOOLEAN);
            $q->when($unread, fn($qq) => $qq->whereNull('read_at'));
        }

        return response()->json($q->paginate($request->integer('per_page',20)));
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'ok' => true,
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->markAsRead();
        
        return response()->json([
            'ok' => true,
        ]);
    }
}

