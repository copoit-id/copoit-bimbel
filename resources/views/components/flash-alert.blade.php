@if (session('success') || session('error'))
<div id="flash-alert"
    class="fixed top-6 right-6 z-50 max-w-sm w-full shadow-lg rounded-lg p-4 text-sm transition-opacity duration-300
        {{ session('success') ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' }}">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="ri-information-line text-lg"></i>
            <span>
                {{ session('success') ?? session('error') }}
            </span>
        </div>
        <button onclick="closeFlashAlert()" class="text-xl leading-none hover:text-gray-600 ml-3">
            &times;
        </button>
    </div>
</div>
<script>
    function closeFlashAlert() {
        const alert = document.getElementById('flash-alert');
        if (alert) {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }
    }
    
    // Auto close after 5 seconds
    setTimeout(() => {
        closeFlashAlert();
    }, 5000);
</script>
@endif
