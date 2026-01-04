<div>
    {{-- Welcome Banner --}}
    <div class="relative overflow-hidden p-8 mb-8 text-white rounded-2xl shadow-xl"
        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);" data-aos="fade-up">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-6 md:mb-0">
                <h1 class="text-3xl font-bold text-white md:text-4xl mb-3">Selamat datang di BALé CMS!</h1>
                <p class="max-w-3xl text-white/90 text-lg mb-2">
                    Platform manajemen konten modern untuk Dinas Kominfo dan Statistik Kabupaten Ponorogo.
                </p>
                <p class="max-w-3xl text-white/80">
                    Kelola berbagai website dalam satu platform yang aman dan powerful.
                </p>
            </div>
            <div class="shrink-0">
                <div class="p-4 bg-white/20 backdrop-blur-md rounded-2xl">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Section --}}
    <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 lg:grid-cols-4" data-aos="fade-up" data-aos-delay="100">
        {{-- Total Sites Card --}}
        <div
            class="group p-6 transition-all duration-300 bg-white border border-gray-100 shadow-md dark:bg-gray-800 rounded-2xl hover:shadow-xl dark:border-gray-700 hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9">
                        </path>
                    </svg>
                </div>
                <span
                    class="px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full dark:bg-blue-900/50 dark:text-blue-300">Total</span>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Website</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">0</p>
            </div>
            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada website</p>
            </div>
        </div>

        {{-- Active Sites Card --}}
        <div
            class="group p-6 transition-all duration-300 bg-white border border-gray-100 shadow-md dark:bg-gray-800 rounded-2xl hover:shadow-xl dark:border-gray-700 hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span
                    class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full dark:bg-green-900/50 dark:text-green-300">Aktif</span>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Website Aktif</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">0</p>
            </div>
            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Semua sistem berjalan</p>
            </div>
        </div>

        {{-- Content Count Card --}}
        <div
            class="group p-6 transition-all duration-300 bg-white border border-gray-100 shadow-md dark:bg-gray-800 rounded-2xl hover:shadow-xl dark:border-gray-700 hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                <span
                    class="px-3 py-1 text-xs font-semibold text-purple-700 bg-purple-100 rounded-full dark:bg-purple-900/50 dark:text-purple-300">Konten</span>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Konten</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">0</p>
            </div>
            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Artikel & halaman</p>
            </div>
        </div>

        {{-- Users Card --}}
        <div
            class="group p-6 transition-all duration-300 bg-white border border-gray-100 shadow-md dark:bg-gray-800 rounded-2xl hover:shadow-xl dark:border-gray-700 hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
                <span
                    class="px-3 py-1 text-xs font-semibold text-amber-700 bg-amber-100 rounded-full dark:bg-amber-900/50 dark:text-amber-300">Users</span>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pengguna</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">0</p>
            </div>
            <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Admin & editor</p>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-3" data-aos="fade-up" data-aos-delay="150">
        <a href="#"
            class="group p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-800 rounded-2xl hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-600 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Buat Website Baru</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tambah website baru</p>
                </div>
            </div>
        </a>

        <a href="#"
            class="group p-6 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border border-purple-200 dark:border-purple-800 rounded-2xl hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-600 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Kelola Konten</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Edit artikel & halaman</p>
                </div>
            </div>
        </a>

        <a href="#"
            class="group p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-800 rounded-2xl hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-600 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Pengaturan</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Konfigurasi sistem</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Recent Activity Section --}}
    <div class="mb-8" data-aos="fade-up" data-aos-delay="200">
        <div class="p-6 bg-white border border-gray-100 shadow-md dark:bg-gray-800 rounded-2xl dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Aktivitas Terbaru</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pantau aktivitas terbaru di platform</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            {{-- Empty State --}}
            <div class="text-center py-12">
                <div
                    class="flex items-center justify-center w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Belum Ada Aktivitas</h4>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Aktivitas akan muncul di sini setelah Anda mulai menggunakan platform.
                </p>
            </div>
        </div>
    </div>

    {{-- CTA Section --}}
    <div class="p-8 mb-8 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl"
        data-aos="fade-up" data-aos-delay="250">
        <div class="max-w-3xl mx-auto text-center">
            <div
                class="flex items-center justify-center w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h2 class="mb-3 text-3xl font-bold text-gray-900 dark:text-white">Siap Untuk Memulai?</h2>
            <p class="mb-8 text-lg text-gray-600 dark:text-gray-300">
                Buat website pertama Anda dan mulai mengelola konten dengan mudah menggunakan BALé CMS.
            </p>
            <a href="#"
                class="inline-flex items-center gap-x-3 px-8 py-3 text-lg font-semibold text-white bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg hover:from-emerald-600 hover:to-teal-600 transition-all shadow-lg hover:shadow-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Buat Website Baru
            </a>
        </div>
    </div>
</div>