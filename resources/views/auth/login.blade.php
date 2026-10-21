<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
<title>Login · {{ \App\Support\Setting::get('agency_name', 'Gulshan-e-Harmain') }}</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen">
<div class="min-h-screen grid lg:grid-cols-2">
  {{-- Brand panel --}}
  <div class="relative hidden lg:flex flex-col items-center justify-center text-center p-12 text-white overflow-hidden bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700">
    <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-brand-500/20 blur-3xl"></div>
    <div class="absolute -bottom-32 -right-24 w-[28rem] h-[28rem] rounded-full bg-gold/20 blur-3xl"></div>
    <img src="{{ agency_logo_url() }}" alt="Gulshan-e-Harmain" class="relative w-80 xl:w-96 drop-shadow-2xl">
    <h1 class="relative mt-8 text-3xl font-bold tracking-tight">{{ \App\Support\Setting::get('agency_name', 'Gulshan-e-Harmain') }}</h1>
    <p class="relative mt-2 text-gold-light font-semibold text-base">(Travel Consultant Hajj & Umrah)</p>
    <p class="relative mt-6 max-w-sm text-emerald-100/80 text-sm leading-relaxed">Bookings, packages, payments, invoices and profit — everything in one simple, secure place.</p>
  </div>

  {{-- Form panel --}}
  <div class="flex items-center justify-center p-6 sm:p-10 bg-white">
    <div class="w-full max-w-md">
      <div class="lg:hidden text-center mb-8">
        <img src="{{ agency_logo_url() }}" alt="Gulshan-e-Harmain" class="w-40 mx-auto drop-shadow">
        <div class="mt-3 text-xl font-bold text-brand-800">{{ \App\Support\Setting::get('agency_name', 'Gulshan-e-Harmain') }}</div>
      </div>
      <h2 class="text-3xl font-bold text-slate-900 tracking-tight">Welcome back</h2>
      <p class="mt-2 text-slate-500">Sign in to your account to continue.</p>

      <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">@csrf
        <div>
          <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
          <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@example.com"
                 class="w-full rounded-xl border border-slate-300 px-4 py-3 text-base placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-brand-500/20 focus:border-brand-600">
          @error('email')<p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
          <div class="relative">
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                   class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-14 text-base placeholder:text-slate-400 focus:outline-none focus:ring-4 focus:ring-brand-500/20 focus:border-brand-600">
            <button type="button" onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Show':'Hide'" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500 hover:text-brand-700">Show</button>
          </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500"> Remember me</label>
        <button class="w-full rounded-xl bg-brand-700 hover:bg-brand-800 text-white font-semibold py-3.5 text-base shadow-lg shadow-brand-700/25 transition">Sign in</button>
      </form>
      <p class="mt-10 text-center text-xs text-slate-400">© {{ date('Y') }} {{ \App\Support\Setting::get('agency_name', 'Gulshan-e-Harmain') }}. All rights reserved.</p>
    </div>
  </div>
</div>
</body></html>
