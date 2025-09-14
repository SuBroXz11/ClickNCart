@extends('layouts.auth')

@section('content')
    <div class="card bg-white shadow-md border p-6">
        <h2 class="text-xl font-semibold text-center mb-4">Register</h2>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label class="label" for="name">Name</label>
                <input type="text" id="name" name="name" class="input input-bordered w-full" required>
            </div>

            <div>
                <label class="label" for="email">Email</label>
                <input type="email" id="email" name="email" class="input input-bordered w-full" required>
            </div>

            <div>
                <label class="label" for="phone">Phone No.</label>
                <input type="text" id="phone" name="phone" class="input input-bordered w-full">
            </div>

            <div>
                <label class="label" for="password">Password</label>
                <input type="password" id="password" name="password" class="input input-bordered w-full" required>
            </div>

            <div>
                <label class="label" for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="input input-bordered w-full" required>
            </div>

            <button type="submit" class="btn btn-primary w-full">Register</button>
        </form>

        <p class="mt-4 text-sm text-center">Already Got an Account?
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Login</a>
        </p>
    </div>
@endsection
