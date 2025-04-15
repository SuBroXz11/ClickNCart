@extends('layouts.auth')

@section('content')
    <div class="card bg-white shadow-md border p-6">
        <h2 class="text-xl font-semibold text-center mb-4">Login</h2>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="label" for="email">Email</label>
                <input type="email" id="email" name="email" class="input input-bordered w-full" required>
            </div>

            <div>
                <label class="label" for="password">Password</label>
                <input type="password" id="password" name="password" class="input input-bordered w-full" required>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn btn-primary w-full">Login</button>
            </div>
        </form>

        <p class="mt-4 text-sm text-center">New Customer?
            <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Register</a>
        </p>
    </div>
@endsection
