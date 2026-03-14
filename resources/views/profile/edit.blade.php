<x-app-layout>
    <div class="px-6 lg:px-8 py-7 max-w-3xl mx-auto space-y-5">

        <div>
            <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight">Settings</h1>
            <p class="text-sm dark:text-slate-400 text-gray-500 mt-1">Manage your profile, password, and account.</p>
        </div>

        <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl p-6 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl p-6 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl p-6 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

    </div>
</x-app-layout>
