@if (session('success'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
        x-transition
        class="flex items-center justify-between bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg mb-4 transition duration-300"
        role="alert"
    >
        <div class="flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button @click="show = false" 
                class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200 transition"
                aria-label="Закрыть">
            <i class="fas fa-times"></i>
        </button>
    </div>
@endif

@if (session('error'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 6000)"
        x-transition
        class="flex items-center justify-between bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg mb-4 transition duration-300"
        role="alert"
    >
        <div class="flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
        <button @click="show = false" 
                class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 transition"
                aria-label="Закрыть">
            <i class="fas fa-times"></i>
        </button>
    </div>
@endif

@if (session('warning'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
        x-transition
        class="flex items-center justify-between bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200 px-4 py-3 rounded-lg mb-4 transition duration-300"
        role="alert"
    >
        <div class="flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i>
            <span>{{ session('warning') }}</span>
        </div>
        <button @click="show = false" 
                class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-200 transition"
                aria-label="Закрыть">
            <i class="fas fa-times"></i>
        </button>
    </div>
@endif

@if (session('info'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
        x-transition
        class="flex items-center justify-between bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg mb-4 transition duration-300"
        role="alert"
    >
        <div class="flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            <span>{{ session('info') }}</span>
        </div>
        <button @click="show = false" 
                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition"
                aria-label="Закрыть">
            <i class="fas fa-times"></i>
        </button>
    </div>
@endif

@if ($errors?->any())
    <div
        x-data="{ show: true }"
        x-show="show"
        x-transition
        class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg mb-4 transition duration-300"
        role="alert"
    >
        <div class="flex justify-between items-start gap-3">
            <div class="flex items-start gap-2 flex-1">
                <i class="fas fa-exclamation-circle mt-1 flex-shrink-0"></i>
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button @click="show = false" 
                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 transition flex-shrink-0"
                    aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
@endif
