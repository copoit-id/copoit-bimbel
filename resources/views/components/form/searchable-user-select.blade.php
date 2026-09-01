@props([
    'users',
    'name' => 'user_id',
    'label' => 'Peserta',
    'selected' => null,
    'required' => false,
    'placeholder' => 'Pilih peserta',
])

@php
    $selectedUser = collect($users)->firstWhere('id', (int) $selected);
@endphp

<div
    x-data="{
        open: false,
        query: '',
        selectedId: @js($selected ? (int) $selected : null),
        users: @js(collect($users)->map(fn ($user) => ['id' => (int) $user->id, 'name' => $user->name, 'email' => $user->email])->values()),
        selectedUser() {
            return this.users.find((user) => user.id === this.selectedId);
        },
        filteredUsers() {
            const query = this.query.trim().toLowerCase();

            if (query === '') {
                return this.users;
            }

            return this.users.filter((user) =>
                user.name.toLowerCase().includes(query) || user.email.toLowerCase().includes(query)
            );
        },
        select(user) {
            this.selectedId = user.id;
            this.query = '';
            this.open = false;
        }
    }"
    @click.outside="open = false"
    class="relative"
>
    <label class="mb-1 block text-sm font-medium text-gray-700" for="{{ $name }}_search">{{ $label }}@if($required)<x-form.required-indicator />@endif</label>
    <input type="hidden" name="{{ $name }}" :value="selectedId">

    <button
        id="{{ $name }}_search"
        type="button"
        @click="open = !open; $nextTick(() => open && $refs.search.focus())"
        @keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.search.focus())"
        class="flex w-full items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
    >
        <span class="min-w-0">
            <template x-if="selectedUser()">
                <span class="block truncate font-medium text-gray-900" x-text="selectedUser().name + ' · ' + selectedUser().email"></span>
            </template>
            <template x-if="!selectedUser()">
                <span class="block truncate text-gray-400">{{ $placeholder }}</span>
            </template>
        </span>
        <i class="ri-arrow-down-s-line shrink-0 text-lg text-gray-500"></i>
    </button>

    <div
        x-show="open"
        x-transition.origin.top
        class="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
        role="listbox"
    >
        <div class="border-b border-gray-100 p-2">
            <div class="relative">
                <i class="ri-search-line pointer-events-none absolute left-3 top-2.5 text-gray-400"></i>
                <input
                    x-ref="search"
                    type="search"
                    x-model="query"
                    @keydown.escape.prevent="open = false"
                    placeholder="Cari nama atau email..."
                    class="w-full rounded-md border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                >
            </div>
        </div>
        <div class="max-h-64 overflow-y-auto p-1">
            <template x-for="user in filteredUsers()" :key="user.id">
                <button
                    type="button"
                    @click="select(user)"
                    class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left hover:bg-primary/5"
                    :class="selectedId === user.id ? 'bg-primary/10' : ''"
                    role="option"
                    :aria-selected="(selectedId === user.id).toString()"
                >
                    <span class="min-w-0"><span class="block truncate text-sm font-medium text-gray-900" x-text="user.name"></span><span class="block truncate text-xs text-gray-500" x-text="user.email"></span></span>
                    <i x-show="selectedId === user.id" class="ri-check-line shrink-0 text-primary"></i>
                </button>
            </template>
            <p x-show="filteredUsers().length === 0" class="px-3 py-5 text-center text-sm text-gray-500">Peserta tidak ditemukan.</p>
        </div>
    </div>

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
