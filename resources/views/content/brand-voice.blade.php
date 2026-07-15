@extends('layouts.app')

@section('title', 'صوت العلامة التجارية')

@section('content')
    <h1 class="text-2xl font-bold mb-6">صوت العلامة التجارية</h1>

    @if ($active)
        <div class="mb-6 bg-indigo-50 border border-indigo-200 rounded-md px-4 py-3 text-sm text-indigo-800">
            النسخة الحالية النشطة: <strong>{{ $active->title }}</strong> — أي حفظ جديد أدناه سيحل محلها فورًا.
        </div>
    @endif

    <form method="POST" action="{{ route('brand-voice.update') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700">العنوان</label>
            <input type="text" name="title" value="{{ old('title', $active?->title) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">إرشادات النبرة</label>
            <textarea name="tone_guidelines" rows="3" required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('tone_guidelines', $active?->tone_guidelines) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">ملاحظات المفردات</label>
            <textarea name="vocabulary_notes" rows="2"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('vocabulary_notes', $active?->vocabulary_notes) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">أشياء يجب تجنبها</label>
            <textarea name="things_to_avoid" rows="2"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('things_to_avoid', $active?->things_to_avoid) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">حقائق عن العلامة التجارية</label>
            <textarea name="brand_facts" rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('brand_facts', $active?->brand_facts) }}</textarea>
        </div>

        <button type="submit" class="bg-gray-900 text-white rounded-md px-5 py-2 font-medium hover:bg-gray-800">
            حفظ وتفعيل
        </button>
    </form>
@endsection
