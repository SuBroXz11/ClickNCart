<!DOCTYPE html>
<html lang="en" data-theme="light" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>ClickNCart Auth</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md p-4">
        {{-- Logo --}}
        <div class="flex justify-center mb-6">
        <a href="/" class="flex items-center gap-2">
                <img src="{{ asset('images/ClicknCart_Logo.png') }}" alt="Logo" class="h-28 w-auto" />
            </a>
        </div>

        {{-- Slot --}}
        @yield('content')
    </div>

</body>
</html>
