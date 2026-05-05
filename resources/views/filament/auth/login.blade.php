@php
    $user = filament()->auth()->user();
@endphp

<div class="min-h-screen bg-gray-50 flex">
    <!-- Left Side - Branding & Message -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-amber-600 to-amber-700 items-center justify-center p-12">
        <div class="max-w-md text-center text-white">
            <div class="mb-8">
                <h1 class="text-5xl font-bold mb-4">Eseven Store</h1>
                <p class="text-lg text-amber-100">Professional Admin Panel</p>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 border border-white/20">
                <svg class="w-16 h-16 mx-auto mb-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <p class="text-sm text-amber-100">
                    Manage products, orders, customers, and all your eCommerce operations from one powerful dashboard.
                </p>
            </div>
            
            <div class="mt-8 text-sm text-amber-100">
                <p>© 2026 Eseven Store. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="flex-1 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Mobile Branding -->
            <div class="lg:hidden text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Eseven Store</h1>
                <p class="text-gray-600">Admin Login</p>
            </div>

            <!-- Login Form Card -->
            <div class="bg-white rounded-lg shadow-md p-8 border border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                    Admin Panel
                </h2>

                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />

                @if (Route::has('password.request'))
                    <div class="mt-4 text-center">
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-amber-600 hover:text-amber-700">
                            {{ __('Forgot your password?') }}
                        </a>
                    </div>
                @endif
            </div>

            <!-- Demo Credentials Info (only in development) -->
            @if (app()->environment('local', 'testing'))
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800 font-semibold mb-2">Demo Credentials:</p>
                    <div class="space-y-1 text-xs text-blue-700">
                        <p><strong>Email:</strong> admin@eseven-store.com</p>
                        <p><strong>Password:</strong> password</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .filament-forms-field-wrapper {
        margin-bottom: 1.5rem;
    }

    .filament-forms-text-input {
        width: 100%;
    }
</style>
