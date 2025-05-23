<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactMessage;
use Tymon\JWTAuth\Facades\JWTAuth;

class ContactController extends Controller
{
    // POST /api/contact
    public function store(Request $request)
    {
        // authenticate via JWT
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        ContactMessage::create([
            'user_id' => $user->id,
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        return response()->json([
            'message' => 'Your message has been sent.'
        ], 201);
    }

    // GET /api/admin/contacts
    public function index()
    {
        $msgs = ContactMessage::with('user')
                 ->orderBy('created_at', 'desc')
                 ->get();

        return response()->json($msgs);
    }
}
