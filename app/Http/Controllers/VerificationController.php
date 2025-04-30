<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerificationController extends Controller
{
    public function showVerificationForm()
    {
        return response()->file(resource_path('views/emails/verify.html'));
    }

 
}