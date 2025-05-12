@if (session('success'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 4000)"
        class="flex items-center justify-between bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-4 transition duration-300"
    >
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button @click="show = false" class="text-green-600 hover:text-green-800">
            <i class="fas fa-times"></i>
        </button>
    </div>
@endif

@if (session('error'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 6000)"
        class="flex items-center justify-between bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-4 transition duration-300"
    >
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span>{{ session('error') }}</span>
        </div>
        <button @click="show = false" class="text-red-600 hover:text-red-800">
            <i class="fas fa-times"></i>
        </button>
    </div>
@endif

@if ($errors?->any())
    <div
        x-data="{ show: true }"
        x-show="show"
        class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded mb-4 transition duration-300"
    >
        <div class="flex justify-between items-start">
            <div class="flex">
                <i class="fas fa-exclamation-circle mr-2 mt-1"></i>
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button @click="show = false" class="text-red-600 hover:text-red-800 ml-4 mt-1">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
@endif
