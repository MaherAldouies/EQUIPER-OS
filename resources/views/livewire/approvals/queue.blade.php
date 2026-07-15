<div class="space-y-8">
    <h1 class="text-2xl font-bold">مراجعة المحتوى</h1>

    <div>
        <h2 class="text-lg font-semibold mb-3">بانتظار المراجعة ({{ $pending->count() }})</h2>

        <div class="space-y-3">
            @forelse ($pending as $approval)
                @php
                    $approvable = $approval->approvable;
                    $label = $approvable instanceof \App\Models\ContentAsset ? $approvable->channel : ($approvable->asset_type ?? 'غير معروف');
                    $body = $approvable instanceof \App\Models\ContentAsset ? $approvable->body : ($approvable->value ?? '');
                @endphp
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-xs uppercase tracking-wide text-gray-500">{{ $label }}</span>
                            <p class="mt-1 text-sm whitespace-pre-line">{{ $body }}</p>
                            <p class="mt-2 text-xs text-gray-400">تنتهي المهلة: {{ $approval->expires_at?->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="flex flex-col gap-2 shrink-0 ms-4">
                            <button type="button" wire:click="approve('{{ $approval->id }}')" class="bg-green-600 text-white text-sm rounded-md px-4 py-1.5 hover:bg-green-700">اعتماد</button>
                            <button type="button" wire:click="startReject('{{ $approval->id }}')" class="bg-red-50 text-red-700 text-sm rounded-md px-4 py-1.5 hover:bg-red-100">رفض</button>
                        </div>
                    </div>

                    @if ($rejectingId === $approval->id)
                        <div class="mt-3 border-t pt-3">
                            <textarea wire:model="reason" rows="2" placeholder="سبب الرفض…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                            @error('reason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            <div class="mt-2 flex gap-2">
                                <button type="button" wire:click="reject('{{ $approval->id }}')" class="bg-red-600 text-white text-sm rounded-md px-4 py-1.5 hover:bg-red-700">تأكيد الرفض</button>
                                <button type="button" wire:click="cancelReject" class="text-sm text-gray-500 hover:underline">إلغاء</button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-gray-500 text-sm">لا يوجد محتوى بانتظار المراجعة.</p>
            @endforelse
        </div>
    </div>

    <div>
        <h2 class="text-lg font-semibold mb-3">قرارات سابقة</h2>
        <div class="bg-white shadow rounded-lg divide-y">
            @forelse ($decided as $approval)
                <div class="p-3 flex items-center justify-between text-sm">
                    <span>{{ class_basename($approval->approvable_type) }} #{{ substr($approval->approvable_id, 0, 8) }}</span>
                    <span @class([
                        'px-2 py-0.5 rounded-full text-xs',
                        'bg-green-100 text-green-800' => $approval->status === 'approved',
                        'bg-red-100 text-red-800' => $approval->status === 'rejected',
                    ])>{{ $approval->status }}</span>
                    <span class="text-gray-400">{{ $approval->decided_at?->format('Y-m-d H:i') }}</span>
                </div>
            @empty
                <p class="p-3 text-gray-500 text-sm">لا توجد قرارات بعد.</p>
            @endforelse
        </div>
    </div>
</div>
