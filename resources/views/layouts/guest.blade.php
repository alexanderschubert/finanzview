<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title') – FinanzView</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 flex items-center justify-center p-6">

    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <img src="{{ asset('finanzview.svg') }}" alt="FinanzView" class="mx-auto mb-4 h-14 w-14">

            <h1 class="text-3xl font-semibold text-white">
                FinanzView
            </h1>

            <p class="text-slate-400 mt-2">
                Persönliche Finanzverwaltung
            </p>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8">

            <h2 class="text-2xl font-semibold text-slate-900">
                @yield('heading')
            </h2>

            <p class="text-slate-500 mt-1 mb-6">
                @yield('intro')
            </p>

            @if (session('status'))
                <div class="mb-5 rounded-xl bg-emerald-50 p-4 text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-red-50 p-4 text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')

        </div>

    </div>

</body>
</html>
