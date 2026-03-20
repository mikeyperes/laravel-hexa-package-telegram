@if(\hexa_core\Models\Setting::isPackageEnabled('hexawebsystems/laravel-hexa-package-telegram'))
@if(auth()->check())

<a href="{{ route('telegram.index') }}"
   class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->is('raw-telegram*') || request()->is('telegram*') ? 'sidebar-active' : 'sidebar-hover' }}">
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
    </svg>
    Telegram
</a>

@endif
@endif
