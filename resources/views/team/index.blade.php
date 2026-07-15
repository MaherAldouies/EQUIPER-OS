@extends('layouts.app')

@section('title', 'الفريق')

@section('content')
    <h1 class="text-2xl font-bold mb-6">الفريق</h1>

    <form method="POST" action="{{ route('team.invite') }}" class="bg-white shadow rounded-lg p-4 mb-6 flex items-end gap-3">
        @csrf
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700">الاسم</label>
            <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700">البريد الإلكتروني</label>
            <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700">الدور</label>
            <select name="role_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-gray-900 text-white rounded-md px-4 py-2 hover:bg-gray-800">دعوة</button>
    </form>

    <div class="bg-white shadow rounded-lg divide-y">
        @foreach ($members as $member)
            <div class="p-3 flex items-center justify-between text-sm">
                <div>
                    <div class="font-medium">{{ $member->name }}</div>
                    <div class="text-gray-500">{{ $member->email }}</div>
                </div>
                <div class="text-gray-500">{{ $member->roles->pluck('name')->join('، ') }}</div>
                <span @class([
                    'px-2 py-0.5 rounded-full text-xs',
                    'bg-yellow-100 text-yellow-800' => $member->status === 'invited',
                    'bg-green-100 text-green-800' => $member->status === 'active',
                ])>{{ $member->status }}</span>
            </div>
        @endforeach
    </div>
@endsection
