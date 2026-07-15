@extends('layouts.guest')

@section('title', 'استعادة كلمة المرور')

@section('content')
    <p class="text-sm text-gray-600 mb-4">أدخل بريدك الإلكتروني وسنرسل لك رابط تعيين كلمة مرور جديدة.</p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">البريد الإلكتروني</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded-md py-2 font-medium hover:bg-gray-800">
            إرسال رابط إعادة التعيين
        </button>

        <div class="text-center text-sm">
            <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">عودة لتسجيل الدخول</a>
        </div>
    </form>
@endsection
