<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'EQUIPER OS') }} — @yield('title', 'لوحة التحكم')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <div class="min-h-screen flex">
        @auth
        <aside class="w-64 bg-gray-900 text-gray-200 flex-shrink-0 hidden md:flex md:flex-col">
            <div class="px-6 py-5 text-xl font-bold text-white border-b border-gray-800">EQUIPER OS</div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : '' }}">لوحة التحكم</a>
                @can('product.view')
                <a href="{{ route('products.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('products.*') ? 'bg-gray-800 text-white' : '' }}">المنتجات</a>
                @endcan
                @can('content.view')
                <a href="{{ route('approvals.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('approvals.*') ? 'bg-gray-800 text-white' : '' }}">مراجعة المحتوى</a>
                <a href="{{ route('content-calendar.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('content-calendar.*') ? 'bg-gray-800 text-white' : '' }}">تقويم المحتوى</a>
                @endcan
                @can('brand_voice.manage')
                <a href="{{ route('brand-voice.edit') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('brand-voice.*') ? 'bg-gray-800 text-white' : '' }}">صوت العلامة التجارية</a>
                @endcan
                @can('campaign.view')
                <a href="{{ route('campaigns.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('campaigns.*') ? 'bg-gray-800 text-white' : '' }}">الحملات</a>
                @endcan
                @can('social.manage')
                <a href="{{ route('inbox.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('inbox.*') ? 'bg-gray-800 text-white' : '' }}">صندوق الوارد الموحد</a>
                @endcan
                @can('team.manage')
                <a href="{{ route('users.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('users.*') ? 'bg-gray-800 text-white' : '' }}">المستخدمون</a>
                @endcan
                @can('task.manage')
                <a href="{{ route('tasks.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('tasks.*') ? 'bg-gray-800 text-white' : '' }}">المهام</a>
                @endcan
                @can('integration.configure')
                <a href="{{ route('settings.integrations') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('settings.*') ? 'bg-gray-800 text-white' : '' }}">إعدادات التكاملات</a>
                @endcan
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="px-3 py-4 border-t border-gray-800">
                @csrf
                <div class="text-sm text-gray-400 mb-2">{{ auth()->user()->name }}</div>
                <button type="submit" class="w-full text-right px-3 py-2 rounded-md hover:bg-gray-800 text-sm">تسجيل الخروج</button>
            </form>
        </aside>
        @endauth

        <main class="flex-1 min-w-0">
            <div class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>
    </div>
    @livewireScripts
</body>
</html>
