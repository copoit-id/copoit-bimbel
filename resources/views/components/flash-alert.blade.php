@if (session('success') || session('error'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
    class="fixed top-6 right-6 z-50 max-w-sm w-full shadow-lg rounded-lg p-4 text-sm
        {{ session('success') ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' }}">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="ri-information-line text-lg"></i>
            <span>
                {{ session('success') ?? session('error') }}
            </span>
        </div>
        <button @click="show = false" class="text-xl leading-none hover:text-gray-600">
            &times;
        </button>
    </div>
</div>
@endif
