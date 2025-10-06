<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Lupa Password - CPNS Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />
    @vite('resources/css/app.css')
</head>

<body class="bg-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-full mb-4">
                <i class="ri-lock-line text-2xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Lupa Password</h1>
            <p class="text-gray-600 mt-2">Masukkan email Anda untuk mendapatkan link reset password</p>
        </div>

        <!-- Form Card -->
        <div class="rounded-2x p-8">
            @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-check-circle-line text-green-500 mr-3"></i>
                    <p class="text-green-700 text-sm">{{ session('success') }}</p>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-error-warning-line text-red-500 mr-3"></i>
                    <p class="text-red-700 text-sm">{{ session('error') }}</p>
                </div>
            </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-mail-line text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors @error('email') border-red-500 @enderror"
                            placeholder="Masukkan email Anda">
                    </div>
                    @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full bg-primary text-white py-3 px-4 rounded-lg font-medium hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2 transition-colors flex items-center justify-center gap-2">
                    <i class="ri-send-plane-line"></i>
                    Kirim Link Reset Password
                </button>
            </form>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm">
                    Ingat password Anda?
                    <a href="{{ route('login') }}" class="text-primary hover:text-primary/80 font-medium">
                        Kembali ke Login
                    </a>
                </p>
            </div>
        </div>

        <!-- Help Text -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Tidak menerima email? Periksa folder spam atau</p>
            <p class="mt-1">hubungi admin untuk bantuan</p>
        </div>
    </div>
</body>

</html>