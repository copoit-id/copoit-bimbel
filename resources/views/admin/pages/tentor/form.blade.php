@if($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <ul class="list-inside list-disc">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="rounded-lg border border-gray-200 bg-white">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 p-6 md:grid-cols-2">
        <div>
            <label for="name" class="mb-2 block text-sm font-semibold text-gray-700">Nama Tutor <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $tentor?->name) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Nama lengkap Tutor">
        </div>

        <div>
            <label for="expertise" class="mb-2 block text-sm font-semibold text-gray-700">Bidang / Keahlian</label>
            <input type="text" id="expertise" name="expertise" value="{{ old('expertise', $tentor?->expertise) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Contoh: UTBK, Matematika, Bahasa Inggris">
        </div>

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $tentor?->email) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="nama@email.com">
        </div>

        <div>
            <label for="phone" class="mb-2 block text-sm font-semibold text-gray-700">Nomor HP</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $tentor?->phone) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="08xxxxxxxxxx">
        </div>

        <div class="md:col-span-2">
            <label for="bio" class="mb-2 block text-sm font-semibold text-gray-700">Catatan / Bio</label>
            <textarea id="bio" name="bio" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Catatan internal tentang tentor">{{ old('bio', $tentor?->bio) }}</textarea>
        </div>

        <div class="md:col-span-2 border-t border-gray-200 pt-5">
            <div class="mb-4">
                <h2 class="text-base font-bold text-gray-900">Akun Portal Tutor</h2>
                <p class="mt-1 text-sm text-gray-500">Akun ini dipakai Tutor untuk login, melihat jadwal, dan mengisi absensi siswa.</p>
            </div>
        </div>

        <div>
            <label for="username" class="mb-2 block text-sm font-semibold text-gray-700">Username <span class="text-red-500">*</span></label>
            <input type="text" id="username" name="username" value="{{ old('username', $account?->username) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Username untuk login">
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-gray-700">{{ $account ? 'Password baru' : 'Password' }} <span class="text-red-500">{{ $account ? '' : '*' }}</span></label>
            <input type="password" id="password" name="password" {{ $account ? '' : 'required' }} class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Minimal 8 karakter">
            @if($account)
                <p class="mt-1 text-xs text-gray-500">Kosongkan jika password tidak perlu diubah.</p>
            @endif
        </div>

        <p class="md:col-span-2 -mt-2 text-xs text-gray-500">Email pada data profil digunakan sebagai email login akun Tutor.</p>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tentor?->is_active ?? true)) class="rounded border-gray-300 text-primary focus:ring-primary">
            Tutor aktif
        </label>
    </div>

    <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-5">
        <a href="{{ route('admin.tentors.index', request()->query()) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
        <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">{{ $buttonLabel }}</button>
    </div>
</form>
