@if(session('admin_login_as'))
<div class="fixed top-0 left-0 right-0 bg-gradient-to-r from-primary to-primary text-white py-2.5 px-4 shadow-lg z-50 border-b-2 border-primary/80">
    <div class="max-w-screen-2xl mx-auto flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 md:gap-3 flex-1 min-w-0">
            <div class="bg-white/20 p-1.5 rounded-full flex-shrink-0">
                <i class="ri-shield-user-line text-base md:text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs md:text-sm font-medium truncate">
                    Anda login sebagai <span class="font-bold">{{ auth()->user()->name }}</span>
                </p>
                <p class="text-[10px] md:text-xs text-white/90 hidden sm:block">
                    Mode Admin Preview - Semua aksi dilakukan atas nama user ini
                </p>
            </div>
        </div>
        <form action="{{ route('logout-as') }}" method="POST" class="flex-shrink-0">
            @csrf
            <button type="submit" 
                class="flex items-center gap-1.5 md:gap-2 bg-white text-primary hover:bg-white/90 px-3 md:px-4 py-1.5 rounded-lg text-xs md:text-sm font-medium transition-colors duration-200 shadow-sm whitespace-nowrap">
                <i class="ri-logout-box-r-line text-sm md:text-base"></i>
                <span class="hidden sm:inline">Kembali ke Admin</span>
                <span class="sm:hidden">Keluar</span>
            </button>
        </form>
    </div>
</div>
<div class="h-[52px]"></div>
@endif
