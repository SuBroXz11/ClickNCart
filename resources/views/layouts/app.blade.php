<!DOCTYPE html>
<html lang="en" data-theme="light" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>ClickNCart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Tailwind CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    {{-- Header --}}
    <header class="bg-base-100 shadow-sm">
    <div class="navbar max-w-7xl mx-auto px-6 py-0 flex-wrap justify-between items-center gap-3">

        {{-- Logo --}}
        <div class="flex items-center gap-2">
            <a href="/" class="flex items-center gap-2">
                <img src="{{ asset('images/ClicknCart_Logo.png') }}" alt="Logo" class="h-24 w-auto" />
            </a>
        </div>

        {{-- Search (lg and up: inline center) --}}
        <div class="hidden lg:flex flex-grow justify-center max-w-md">
            <form class="flex w-full">
                <input type="text" placeholder="Search Products..." class="input input-bordered w-full rounded-r-none" />
                <button type="submit" class="btn btn-primary rounded-l-none">Search</button>
            </form>
        </div>

        {{-- Right side buttons (lg and up) --}}
        <div class="hidden lg:flex items-center gap-3">
            {{-- Cart --}}
            <a href="/cart" class="btn btn-outline btn-sm relative">
                <i class="fas fa-shopping-cart"></i>
                <span class="ml-1">Cart</span>
                <span class="badge badge-primary badge-sm absolute -top-2 -right-2">1</span>
            </a>

            {{-- Auth --}}
            @guest
                <a href="/login" class="btn btn-sm btn-neutral">Login</a>
                <a href="/register" class="btn btn-sm btn-outline">Register</a>
            @else
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-sm btn-ghost capitalize">
                        {{ Auth::user()->name }}
                    </label>
                    <ul tabindex="0" class="dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52 text-base-content">
                        <li><a href="/profile">Profile</a></li>
                        <li><hr class="my-1"></li>
                        <li>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit" class="text-red-500 w-full text-left">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endguest
        </div>

        {{-- Hamburger (mobile only) --}}
        <div class="dropdown dropdown-end lg:hidden ml-auto">
            <label tabindex="0" class="btn btn-ghost btn-circle">
                <i class="fas fa-bars text-xl"></i>
            </label>
            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-3 shadow bg-base-100 rounded-box w-64 gap-2">
                <li><a href="/cart">🛒 Cart <span class="badge badge-primary">1</span></a></li>
                @guest
                    <li><a href="/login">Login</a></li>
                    <li><a href="/register">Register</a></li>
                @else
                    <li><a href="/profile">Profile</a></li>
                    <li>
                        <form method="POST" action="/logout">
                            @csrf
                            <button type="submit" class="text-red-500 w-full text-left">Logout</button>
                        </form>
                    </li>
                @endguest
            </ul>
        </div>

        {{-- Search bar on mobile (below logo, full width) --}}
        <div class="w-full block lg:hidden">
            <form class="flex max-w-md mx-auto mt-2">
                <input type="text" placeholder="Search Products..." class="input input-bordered w-full rounded-r-none" />
                <button type="submit" class="btn btn-primary rounded-l-none">Search</button>
            </form>
        </div>

    </div>
</header>



    {{-- Main --}}
    <main class="max-w-7xl mx-auto px-4 py-6">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="footer footer-center p-6 bg-base-200 text-base-content border-t">
        <aside>
            <p>&copy; {{ date('Y') }} ClickNCart</p>
        </aside>
    </footer>

</body>
</html>
