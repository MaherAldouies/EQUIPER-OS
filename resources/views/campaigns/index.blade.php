@extends('layouts.app')

@section('title', 'الحملات')

@section('content')
    <h1 class="text-2xl font-bold mb-6">الحملات</h1>

    @can('campaign.manage')
    <form method="POST" action="{{ route('campaigns.store') }}" class="bg-white shadow rounded-lg p-4 mb-6 flex items-end gap-3">
        @csrf
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700">اسم الحملة</label>
            <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700">الهدف</label>
            <input type="text" name="goal" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <button type="submit" class="bg-gray-900 text-white rounded-md px-4 py-2 hover:bg-gray-800">إنشاء</button>
    </form>
    @endcan

    <div class="space-y-3">
        @forelse ($campaigns as $campaign)
            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold">{{ $campaign->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $campaign->goal }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $campaign->contentAssets()->count() }} أصل محتوى مرتبط</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span @class([
                            'px-2 py-0.5 rounded-full text-xs',
                            'bg-gray-100 text-gray-800' => $campaign->status === 'draft',
                            'bg-green-100 text-green-800' => $campaign->status === 'active',
                            'bg-blue-100 text-blue-800' => $campaign->status === 'completed',
                        ])>{{ $campaign->status }}</span>

                        @can('complete', $campaign)
                            <form method="POST" action="{{ route('campaigns.complete', $campaign) }}">
                                @csrf
                                <button type="submit" class="text-xs text-indigo-600 hover:underline">إغلاق الحملة</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-sm">لا توجد حملات بعد.</p>
        @endforelse
    </div>
@endsection
