<div>
    <h1 class="text-2xl font-bold mb-6">صندوق الوارد الموحد</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white shadow rounded-lg overflow-hidden" style="min-height: 400px;">
        <div class="md:col-span-1 border-e divide-y overflow-y-auto">
            @forelse ($conversations as $conversation)
                <button
                    type="button"
                    wire:click="openConversation('{{ $conversation->external_conversation_id }}')"
                    @class([
                        'w-full text-right p-3 text-sm hover:bg-gray-50',
                        'bg-indigo-50' => $activeConversationId === $conversation->external_conversation_id,
                    ])
                >
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ $conversation->from_name ?? $conversation->external_conversation_id }}</span>
                        <span class="text-xs text-gray-400">{{ strtoupper($conversation->provider) }}</span>
                    </div>
                    <p class="text-gray-500 truncate">{{ $conversation->body }}</p>
                </button>
            @empty
                <p class="p-4 text-gray-500 text-sm">لا توجد محادثات بعد.</p>
            @endforelse
        </div>

        <div class="md:col-span-2 flex flex-col">
            @if ($activeConversationId)
                <div class="flex-1 p-4 space-y-3 overflow-y-auto">
                    @foreach ($thread as $message)
                        <div @class([
                            'max-w-md rounded-lg px-3 py-2 text-sm',
                            'bg-gray-100 me-auto' => $message->direction === 'inbound',
                            'bg-indigo-600 text-white ms-auto' => $message->direction === 'outbound',
                        ])>
                            {{ $message->body }}
                        </div>
                    @endforeach
                </div>

                <div class="border-t p-3">
                    <div class="flex gap-2">
                        <input type="text" wire:model="reply" wire:keydown.enter="send" placeholder="اكتب ردًا…" class="flex-1 rounded-md border-gray-300 text-sm">
                        <button type="button" wire:click="send" class="bg-gray-900 text-white rounded-md px-4 py-2 text-sm hover:bg-gray-800">إرسال</button>
                    </div>
                    @error('reply') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            @else
                <div class="flex-1 flex items-center justify-center text-gray-400 text-sm">اختر محادثة لعرضها</div>
            @endif
        </div>
    </div>
</div>
