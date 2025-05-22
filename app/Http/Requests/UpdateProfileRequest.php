<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'name'             => 'required|string|max:255',
            'address'          => 'nullable|string|max:500',
            'phone_number'     => 'nullable|string|max:20',
            'profile_picture'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}


