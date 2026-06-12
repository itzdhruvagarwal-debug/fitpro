<div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-gray-900 via-slate-800 to-indigo-950 px-6 py-12">
    <div class="relative w-full max-w-md">
        <!-- Glow background effect -->
        <div class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-violet-600 to-pink-600 opacity-30 blur-lg transition duration-1000 group-hover:opacity-100"></div>

        <!-- Glassmorphism Card -->
        <div class="relative rounded-2xl border border-gray-700 bg-gray-900/80 p-8 shadow-2xl backdrop-blur-xl">
            <div class="flex flex-col items-center justify-center pb-6">
                <!-- Lock Icon with Glow -->
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-violet-500/10 text-violet-400 ring-8 ring-violet-500/5">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <h2 class="mt-4 text-2xl font-bold tracking-tight text-white">Two-Factor Authentication</h2>
                <p class="mt-2 text-center text-sm text-gray-400">Enter the 6-digit verification code from your authenticator app.</p>
            </div>

            <form wire:submit="verify" class="space-y-6">
                <div>
                    <label for="code" class="block text-sm font-medium leading-6 text-gray-200">Verification Code</label>
                    <div class="mt-2">
                        <input wire:model="code" id="code" name="code" type="text" autocomplete="one-time-code" required maxlength="6" autofocus placeholder="000000"
                            class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 py-3 text-center text-2xl font-bold tracking-[0.5em] text-white placeholder-gray-600 shadow-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 sm:text-2xl sm:leading-6">
                    </div>
                    @error('code')
                        <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit"
                        class="flex w-full justify-center rounded-lg bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg transition duration-200 hover:from-violet-500 hover:to-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600">
                        Verify Code
                    </button>

                    <button type="button" onclick="document.getElementById('logout-form').submit();"
                        class="flex w-full justify-center rounded-lg border border-gray-700 bg-transparent px-4 py-3 text-sm font-medium text-gray-400 shadow-sm transition duration-200 hover:bg-gray-800/30 hover:text-white">
                        Cancel & Logout
                    </button>
                </div>
            </form>

            <form id="logout-form" action="{{ route('filament.superadmin.auth.logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>
</div>
