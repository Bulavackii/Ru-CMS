@extends('layouts.admin')

@section('title', 'Отчёт об ошибке')

@section('content')
    <h1 class="text-3xl font-extrabold mb-6 text-gray-800 flex items-center gap-2">
        🐞 Сообщить об ошибке
    </h1>

    @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center gap-2">
            <i class="fas fa-check-circle text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form method="POST"
          action="{{ route('admin.error.report.send') }}"
          enctype="multipart/form-data"
          class="bg-white border border-gray-200 p-6 rounded-xl shadow-md space-y-6 max-w-2xl"
          x-data="{
              fileName: '',
              filePreview: '',
              handleFileChange(event) {
                  const file = event.target.files[0];
                  this.fileName = file?.name || '';
                  if (file && file.type.startsWith('image/')) {
                      const reader = new FileReader();
                      reader.onload = e => this.filePreview = e.target.result;
                      reader.readAsDataURL(file);
                  } else {
                      this.filePreview = '';
                  }
              }
          }">

        @csrf

        {{-- 📝 Сообщение --}}
        <div>
            <label for="message" class="block font-semibold text-gray-700 mb-2">
                Сообщение об ошибке <span class="text-red-500">*</span>
            </label>
            <textarea id="message" name="message" rows="5"
                      placeholder="Опишите, что именно не работает и как это воспроизвести..."
                      class="w-full border rounded-lg px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-400 focus:outline-none resize-none @error('message') border-red-500 @enderror"
                      required>{{ old('message') }}</textarea>
            @error('message')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- 📧 Email --}}
        <div>
            <label for="email" class="block font-semibold text-gray-700 mb-2">
                Ваш E-mail (по желанию)
            </label>
            <input type="email" id="email" name="email"
                   value="{{ old('email') }}"
                   placeholder="you@example.com"
                   class="w-full border rounded-lg px-4 py-3 shadow-sm focus:ring-2 focus:ring-blue-400 focus:outline-none @error('email') border-red-500 @enderror">
            @error('email')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- 📎 Drag & Drop + preview --}}
        <div>
            <label for="file" class="block font-semibold text-gray-700 mb-2">
                Прикрепить файл (скриншот или лог)
            </label>

            <label for="file"
                   class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 transition-all duration-150 text-gray-600"
                   @drop.prevent="handleFileChange($event)"
                   @dragover.prevent>
                <input type="file" id="file" name="file" class="hidden"
                       accept=".jpg,.jpeg,.png,.gif,.log,.txt,.pdf"
                       @change="handleFileChange($event)">

                <template x-if="!filePreview">
                    <div class="text-center">
                        <i class="fas fa-cloud-upload-alt text-3xl mb-2"></i>
                        <p class="text-sm">Нажмите или перетащите файл сюда</p>
                        <p class="text-xs text-gray-400 mt-1">(до 2 MB)</p>
                    </div>
                </template>

                <template x-if="filePreview">
                    <img :src="filePreview" alt="Превью"
                         class="h-32 rounded object-contain border border-gray-300 shadow mt-2" />
                </template>
            </label>

            <template x-if="fileName">
                <p class="mt-2 text-sm text-blue-600 font-medium truncate">
                    <i class="fas fa-paperclip mr-1"></i> <span x-text="fileName"></span>
                </p>
            </template>

            @error('file')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- 🚀 Кнопка --}}
        <div class="flex justify-end pt-4">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg shadow flex items-center gap-2 transition-all duration-200">
                <i class="fas fa-paper-plane"></i> Отправить
            </button>
        </div>
    </form>
@endsection
