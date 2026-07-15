<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">المنتجات</h1>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:click="toggleMiscategorized" @checked($onlyMiscategorized) class="rounded border-gray-300">
            عرض المنتجات ذات التصنيف الخاطئ فقط
        </label>
    </div>

    @can('product.manage_category')
    <div class="mb-4 flex items-center gap-3 bg-white border rounded-md p-3">
        <span class="text-sm text-gray-600">{{ count($selected) }} منتج محدد</span>
        <select wire:model="bulkCategoryId" class="rounded-md border-gray-300 text-sm">
            <option value="">اختر التصنيف الصحيح…</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="bulkRecategorize" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">
            إعادة تصنيف المحدد
        </button>
    </div>
    @endcan

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2"></th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500">المنتج</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500">تصنيف سلة</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500">تصنيف EQUIPER</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500">المخزون</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500">الحالة</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-3 py-2">
                            <input type="checkbox" wire:model="selected" value="{{ $product->id }}" class="rounded border-gray-300">
                        </td>
                        <td class="px-3 py-2 font-medium">{{ $product->name }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $product->salla_category_name ?? '—' }}</td>
                        <td class="px-3 py-2">
                            @if ($product->category)
                                {{ $product->category->name }}
                            @else
                                <span class="text-red-600">بدون تصنيف</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span @class([
                                'px-2 py-0.5 rounded-full text-xs',
                                'bg-green-100 text-green-800' => $product->stock_status === 'in_stock',
                                'bg-yellow-100 text-yellow-800' => $product->stock_status === 'low_stock',
                                'bg-red-100 text-red-800' => $product->stock_status === 'out_of_stock',
                            ])>{{ $product->stock_status }}</span>
                        </td>
                        <td class="px-3 py-2 text-gray-500">{{ $product->lifecycle_state }}</td>
                        <td class="px-3 py-2 text-left">
                            @can('product.manage_category')
                                @if ($product->lifecycle_state === 'draft' && $product->category_id)
                                    <form method="POST" action="{{ route('products.enrich', $product) }}">
                                        @csrf
                                        <button type="submit" class="text-indigo-600 hover:underline text-xs">إثراء</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">لا توجد منتجات.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</div>
