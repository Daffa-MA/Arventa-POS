<x-layouts.admin title="Arventa Developer Login">
    <section class="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-10">
        <div class="w-full max-w-md rounded-2xl border border-white/10 bg-white p-6 shadow-2xl shadow-slate-950/30">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Arventa Developer</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-950">Developer Console</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Masuk sebagai developer/operator Arventa untuk generate dan deploy POS pembeli.</p>
            </div>

            @if (session('status'))
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('developer.login.store') }}" class="mt-6 grid gap-4">
                @csrf
                <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Username atau email developer
                    <input name="login" value="{{ old('login') }}" autocomplete="username" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                    @error('login') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="grid gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Password
                    <input name="password" type="password" autocomplete="current-password" required class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-medium normal-case tracking-normal text-slate-900 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-100">
                    @error('password') <span class="text-xs font-medium normal-case tracking-normal text-red-600">{{ $message }}</span> @enderror
                </label>
                <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 active:scale-[0.98]">Masuk Developer</button>
            </form>
        </div>
    </section>
</x-layouts.admin>
