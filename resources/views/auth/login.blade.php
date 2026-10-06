<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Tekrar hoş geldiniz</h2>
        <p class="mt-2 text-sm text-gray-500">Devam etmek için panel hesabınızla giriş yapın.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5"
          x-data="{ showPassword: false, loading: false }" x-on:submit="loading = true">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <x-icon name="mail" class="h-5 w-5" />
                </span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       placeholder="ornek@klinik.com"
                       @class([
                           'block w-full rounded-xl border bg-white py-3 pl-11 pr-4 text-sm shadow-sm transition placeholder:text-gray-400 focus:ring-4',
                           'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('email'),
                           'border-gray-300 focus:border-primary-500 focus:ring-primary-100' => ! $errors->has('email'),
                       ]) />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-primary-600 hover:text-primary-800 focus:outline-none focus:underline" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <x-icon name="lock" class="h-5 w-5" />
                </span>
                <input id="password" name="password" required autocomplete="current-password"
                       x-bind:type="showPassword ? 'text' : 'password'" type="password"
                       placeholder="••••••••"
                       @class([
                           'block w-full rounded-xl border bg-white py-3 pl-11 pr-12 text-sm shadow-sm transition placeholder:text-gray-400 focus:ring-4',
                           'border-red-300 focus:border-red-500 focus:ring-red-100' => $errors->has('password'),
                           'border-gray-300 focus:border-primary-500 focus:ring-primary-100' => ! $errors->has('password'),
                       ]) />
                <button type="button" x-on:click="showPassword = ! showPassword"
                        class="absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-400 transition hover:text-gray-600 focus:outline-none focus:text-primary-600"
                        x-bind:aria-label="showPassword ? 'Şifreyi gizle' : 'Şifreyi göster'" aria-label="Şifreyi göster">
                    <x-icon name="eye" class="h-5 w-5" x-show="! showPassword" />
                    <x-icon name="eye-slash" class="h-5 w-5" x-show="showPassword" x-cloak />
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="inline-flex cursor-pointer select-none items-center">
            <input id="remember_me" type="checkbox" name="remember"
                   class="h-4 w-4 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
        </label>

        <button type="submit" x-bind:disabled="loading"
                class="group flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-600/25 transition hover:from-primary-700 hover:to-primary-800 hover:shadow-primary-700/30 focus:outline-none focus:ring-4 focus:ring-primary-200 disabled:cursor-wait disabled:opacity-80">
            <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span x-text="loading ? 'Giriş yapılıyor…' : '{{ __('Log in') }}'">{{ __('Log in') }}</span>
            <x-icon name="chevron-right" class="h-4 w-4 transition group-hover:translate-x-0.5" x-show="! loading" />
        </button>
    </form>

    <div class="mt-8 flex items-center gap-2 rounded-xl bg-gray-50 px-4 py-3 text-xs text-gray-500 lg:bg-gray-50">
        <x-icon name="lock" class="h-4 w-4 text-gray-400" />
        Bu panel yalnızca klinik personeli içindir. Hesabınız yoksa yöneticinize başvurun.
    </div>
</x-guest-layout>
