@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
    <h1 class="text-2xl font-bold mb-6">لوحة التحكم</h1>

    @php
        $card = function (string $title, $signal, string $unit = '') {
            return ['title' => $title, 'signal' => $signal, 'unit' => $unit];
        };
        $cards = [];
        if ($canViewRevenue) {
            $cards[] = $card('الإيرادات اليوم', $revenueSignal, 'ر.س');
            $cards[] = $card('عدد الطلبات اليوم', $orderCountSignal);
        }
        $cards[] = $card('محتوى بانتظار المراجعة', $contentPipelineSignal);
        $cards[] = $card('نقرات البحث العضوي', $organicClicksSignal);
        $cards[] = $card('جلسات Google Analytics (اليوم)', $ga4SessionsSignal);
        $cards[] = $card('مستخدمو Google Analytics (اليوم)', $ga4UsersSignal);
        $cards[] = $card('تحويلات Google Analytics (اليوم)', $ga4ConversionsSignal);
        $cards[] = $card('منتجات Merchant Center النشطة', $merchantActiveProductsSignal);
        $cards[] = $card('منتجات بها مشاكل (Merchant Center)', $merchantIssuesSignal);
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach ($cards as $c)
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm text-gray-500">{{ $c['title'] }}</div>
                @if ($c['signal'])
                    <div class="mt-1 text-2xl font-bold">
                        {{ number_format((float) $c['signal']->value, $c['signal']->unit === 'SAR' ? 2 : 0) }}
                        <span class="text-sm font-normal text-gray-500">{{ $c['unit'] }}</span>
                    </div>
                    @if ($c['signal']->confidence === 'low')
                        <div class="text-xs text-amber-600 mt-1">بيانات غير كافية بعد</div>
                    @endif
                @else
                    <div class="mt-1 text-lg font-medium text-gray-400">بيانات غير كافية</div>
                @endif
            </div>
        @endforeach
    </div>

    <h2 class="text-lg font-semibold mb-3">حالة التكاملات</h2>
    <div class="bg-white shadow rounded-lg divide-y">
        @forelse ($integrations as $integration)
            <div class="p-3 flex items-center justify-between text-sm">
                <span class="font-medium">{{ $integration->provider }}</span>
                <span @class([
                    'px-2 py-0.5 rounded-full text-xs',
                    'bg-green-100 text-green-800' => $integration->status === 'connected',
                    'bg-yellow-100 text-yellow-800' => $integration->status === 'configuring',
                    'bg-red-100 text-red-800' => $integration->status === 'degraded',
                ])>{{ $integration->status }}</span>
                <span class="text-gray-400">{{ $integration->last_successful_sync_at?->diffForHumans() ?? 'لم تتم المزامنة بعد' }}</span>
            </div>
        @empty
            <p class="p-3 text-gray-500 text-sm">لا توجد تكاملات مهيأة بعد.</p>
        @endforelse
    </div>
@endsection
