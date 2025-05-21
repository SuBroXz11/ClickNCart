<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        return response()->json(Auth::user());
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();

        $data = $request->only([
            'name',
            'address',
            'phone_number',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request
                ->file('profile_picture')
                ->store('profile_pictures', 'public');
            $data['profile_picture'] = $path;
        }

        $user->update($data);

        return response()->json($user);
    }
}
