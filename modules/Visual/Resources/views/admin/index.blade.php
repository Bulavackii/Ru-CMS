@extends('layouts.admin')
@section('title','Визуальный редактор')

@section('content')
  <h1 class="text-2xl font-bold mb-6">🎨 Визуальные части и темы</h1>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-900 p-4 rounded shadow">
      <h2 class="font-semibold mb-2">Темы</h2>
      <a href="{{ route('admin.visual.themes.index') }}" class="text-blue-600">Управление темами</a>
    </div>
    <div class="bg-white dark:bg-gray-900 p-4 rounded shadow">
      <h2 class="font-semibold mb-2">Фрагменты</h2>
      <a href="{{ route('admin.visual.fragments.index') }}" class="text-blue-600">Управление фрагментами</a>
    </div>
  </div>
@endsection
