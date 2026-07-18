@extends('layouts.app')

@section('title', 'اختيار صفحة فيسبوك')

@section('content')
    <h1 class="text-2xl font-bold mb-2">اختر الصفحة اللي عايز تربطها</h1>
    <p class="text-sm text-gray-500 mb-6">حسابك على Facebook بيدير أكثر من صفحة — اختر الصفحة اللي هتُنشر منها ويتصل حساب Instagram المرتبط بيها (لو موجود).</p>

    <div class="bg-white shadow rounded-lg divide-y max-w-xl">
        @foreach ($pages as $page)
            <form method="POST" action="{{ route('integrations.meta.select-page') }}" class="p-4 flex items-center justify-between">
                @csrf
                <div>
                    <div class="font-medium">{{ $page['name'] }}</div>
                    <div class="text-xs text-gray-400">
                        @if ($page['ig_user_id'])
                            مرتبطة بحساب Instagram
                        @else
                            بدون حساب Instagram مرتبط
                        @endif
                    </div>
                </div>
                <input type="hidden" name="page_id" value="{{ $page['id'] }}">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">اختيار</button>
            </form>
        @endforeach
    </div>
@endsection
