@extends('layouts.app')

@section('title', 'تقويم المحتوى')

@section('content')
    <h1 class="text-2xl font-bold mb-6">تقويم المحتوى</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <h2 class="text-lg font-semibold mb-3">معتمد — جاهز للجدولة</h2>
            <div class="space-y-3">
                @forelse ($approved as $asset)
                    <div class="bg-white shadow rounded-lg p-4">
                        <span class="text-xs uppercase tracking-wide text-gray-500">{{ $asset->channel }}</span>
                        @if ($asset->channel === 'tiktok_video')
                            <span class="text-xs text-amber-600">(نشر فقط — تيك توك لا يوفر API للرد على التعليقات)</span>
                        @endif
                        <p class="mt-1 text-sm whitespace-pre-line">{{ \Illuminate\Support\Str::limit($asset->body, 140) }}</p>
                        <form method="POST" action="{{ route('content-calendar.schedule', $asset) }}" class="mt-3 flex items-center gap-2">
                            @csrf
                            <input type="datetime-local" name="scheduled_for" required class="rounded-md border-gray-300 text-sm">
                            <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-3 py-1.5 hover:bg-gray-800">جدولة</button>
                        </form>
                        @can('publish', $asset)
                            @if (in_array($asset->channel, ['instagram_caption', 'facebook_post', 'tiktok_video', 'x_post']))
                                <form method="POST" action="{{ route('content-calendar.publish-now', $asset) }}" class="mt-2">
                                    @csrf
                                    <button type="submit" class="bg-indigo-600 text-white text-sm rounded-md px-3 py-1.5 hover:bg-indigo-700">نشر مباشر الآن</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">لا يوجد محتوى معتمد بانتظار الجدولة.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold mb-3">مجدول</h2>
            <div class="space-y-3">
                @forelse ($scheduled as $asset)
                    <div class="bg-white shadow rounded-lg p-4">
                        <span class="text-xs uppercase tracking-wide text-gray-500">{{ $asset->channel }}</span>
                        <p class="mt-1 text-sm whitespace-pre-line">{{ \Illuminate\Support\Str::limit($asset->body, 140) }}</p>
                        <p class="mt-2 text-xs text-gray-400">موعد النشر: {{ $asset->scheduled_for?->format('Y-m-d H:i') }}</p>
                        <form method="POST" action="{{ route('content-calendar.confirm-published', $asset) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white text-sm rounded-md px-3 py-1.5 hover:bg-green-700">تأكيد النشر</button>
                        </form>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">لا يوجد محتوى مجدول حاليًا.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
