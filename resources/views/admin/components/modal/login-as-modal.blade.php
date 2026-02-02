<!-- Login As User Modal -->
<div id="login-as-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900">
                        Login Sebagai User
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Pilih user yang ingin Anda login sebagai</p>
                </div>
                <button type="button"
                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                    data-modal-hide="login-as-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-5 space-y-4">
                <!-- Search Box -->
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ri-search-line text-gray-400"></i>
                    </div>
                    <input type="text" id="modal-user-search"
                        class="block w-full p-3 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary focus:border-primary"
                        placeholder="Cari nama atau email user...">
                </div>

                <!-- User List -->
                <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                    <div id="modal-user-list" class="divide-y divide-gray-200">
                        <!-- Loading state -->
                        <div class="flex items-center justify-center p-8">
                            <div class="text-center">
                                <i class="ri-loader-4-line text-3xl text-gray-400 animate-spin"></i>
                                <p class="text-sm text-gray-500 mt-2">Memuat data user...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('login-as-modal');
        const searchInput = document.getElementById('modal-user-search');
        const userListContainer = document.getElementById('modal-user-list');
        let allUsers = [];

        // Load users when modal is opened
        modal.addEventListener('show.bs.modal', loadUsers);
        
        // Flowbite modal event
        document.querySelectorAll('[data-modal-toggle="login-as-modal"]').forEach(button => {
            button.addEventListener('click', function() {
                loadUsers();
            });
        });

        function loadUsers() {
            fetch('{{ route("admin.user.index") }}?ajax=1')
                .then(response => response.json())
                .then(data => {
                    allUsers = data.users;
                    renderUsers(allUsers);
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    userListContainer.innerHTML = `
                        <div class="flex items-center justify-center p-8">
                            <div class="text-center">
                                <i class="ri-error-warning-line text-3xl text-red-400"></i>
                                <p class="text-sm text-gray-500 mt-2">Gagal memuat data user</p>
                            </div>
                        </div>
                    `;
                });
        }

        function renderUsers(users) {
            if (users.length === 0) {
                userListContainer.innerHTML = `
                    <div class="flex items-center justify-center p-8">
                        <div class="text-center">
                            <i class="ri-user-line text-3xl text-gray-400"></i>
                            <p class="text-sm text-gray-500 mt-2">Tidak ada user ditemukan</p>
                        </div>
                    </div>
                `;
                return;
            }

            userListContainer.innerHTML = users.map(user => `
                <div class="p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=444444&color=fff"
                                class="w-10 h-10 rounded-full flex-shrink-0" alt="${user.name}">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate">${user.name}</p>
                                <p class="text-sm text-gray-500 truncate">${user.email}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full ${user.status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                        ${user.status === 'aktif' ? 'Aktif' : 'Tidak Aktif'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <form action="/admin/user/${user.id}/login-as" method="POST" onsubmit="return confirm('Anda akan login sebagai ${user.name}. Lanjutkan?')">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button type="submit"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors whitespace-nowrap">
                                <i class="ri-user-shared-line"></i>
                                <span>Login As</span>
                            </button>
                        </form>
                    </div>
                </div>
            `).join('');
        }

        // Search functionality
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const filteredUsers = allUsers.filter(user =>
                user.name.toLowerCase().includes(searchTerm) ||
                user.email.toLowerCase().includes(searchTerm)
            );
            renderUsers(filteredUsers);
        });
    });
</script>
