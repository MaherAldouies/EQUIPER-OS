@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">البريد الإلكتروني</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">كلمة المرور</label>
            <input id="password" type="password" name="password" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" class="rounded border-gray-300">
            تذكرني
        </label>

        <button type="submit" class="w-full bg-gray-900 text-white rounded-md py-2 font-medium hover:bg-gray-800">
            دخول
        </button>

        <div class="text-center text-sm">
            <a href="{{ route('password.request') }}" class="text-indigo-600 hover:underline">نسيت كلمة المرور؟</a>
        </div>
    </form>
@endsection
