<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
  <title>@yield('title', 'Dashboard') · {{ \App\Support\Setting::get('agency_name', 'Gulshan-e-Harmain') }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
@php
  $nav = [
    ['dashboard', 'Dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
    ['leads.index', 'Leads / Inquiries', 'M8 10h.01M12 10h.01M16 10h.01M21 12c0 4-4 7-9 7a10 10 0 01-4-.8L3 20l1.3-3.9A6.7 6.7 0 013 12c0-4 4-7 9-7s9 3 9 7z'],
    ['bookings.index', 'Bookings / Orders', 'M9 12h6M9 16h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z'],
    ['packages.index', 'Packages', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    ['customers.index', 'Customers', 'M17 20h5v-2a4 4 0 00-5-3.9M9 11a4 4 0 100-8 4 4 0 000 8zm-7 9v-2a4 4 0 014-4h6a4 4 0 014 4v2'],
    ['invoices.index', 'Invoices', 'M9 14l2 2 4-4M7 3h10a2 2 0 012 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 012-2z'],
    ['payments.index', 'Payments', 'M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
    ['expenses.index', 'Expenses', 'M12 8c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z'],
  ];
  if (auth()->user()->isAdmin()) {
    $nav[] = ['reports.index', 'Reports & Profit', 'M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z'];
    $nav[] = ['settings.edit', 'Settings & Users', 'M10.3 4.3a1.7 1.7 0 013.4 0 1.7 1.7 0 002.5 1.1 1.7 1.7 0 011.7 2.9 1.7 1.7 0 000 3.4 1.7 1.7 0 01-1.7 2.9 1.7 1.7 0 00-2.5 1.1 1.7 1.7 0 01-3.4 0 1.7 1.7 0 00-2.5-1.1 1.7 1.7 0 01-1.7-2.9 1.7 1.7 0 000-3.4 1.7 1.7 0 011.7-2.9 1.7 1.7 0 002.5-1.1zM12 15a3 3 0 100-6 3 3 0 000 6z'];
  }
@endphp
<input type="checkbox" id="menu" class="peer hidden">

{{-- ============ Top header ============ --}}
<header class="bg-white sticky top-0 z-40 h-16 border-b-2 border-gold shadow-sm">
  <div class="h-full px-4 lg:px-6 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3 lg:gap-4 min-w-0">
      <label for="menu" class="lg:hidden btn btn-light !px-2.5 !py-2" aria-label="Menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg></label>
      <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
        <img src="{{ agency_logo_url() }}" alt="Gulshan-e-Harmain" class="h-10 w-10 lg:h-12 lg:w-12 shrink-0 drop-shadow-sm">
        <span class="min-w-0 leading-tight">
          <span class="block text-base lg:text-lg font-bold text-brand-800 truncate">{{ \App\Support\Setting::get('agency_name', 'Gulshan-e-Harmain') }}</span>
          <span class="block text-[11px] lg:text-xs font-semibold text-gold truncate">{{ \App\Support\Setting::get('agency_tagline', '(Travel Consultant Hajj & Umrah)') }}</span>
        </span>
      </a>
    </div>

    <div class="flex items-center gap-3 lg:gap-4">
      <a href="{{ route('bookings.create') }}" class="btn btn-primary hidden sm:inline-flex"><span class="text-base leading-none">+</span> New Booking</a>

      <details class="relative" id="userMenu">
        <summary class="list-none cursor-pointer flex items-center gap-3 rounded-full py-1 pl-1 pr-1 sm:pr-3 hover:bg-slate-100 transition [&::-webkit-details-marker]:hidden">
          @include('partials.avatar', ['user' => auth()->user(), 'size' => 'h-9 w-9'])
          <span class="hidden sm:block text-left leading-tight">
            <span class="block text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</span>
            <span class="block text-xs text-slate-500 capitalize">{{ auth()->user()->role }}</span>
          </span>
          <svg class="hidden sm:block w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="absolute right-0 mt-2 w-64 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden">
          <div class="px-4 py-4 bg-brand-50 flex items-center gap-3 border-b border-slate-100">
            @include('partials.avatar', ['user' => auth()->user(), 'size' => 'h-12 w-12'])
            <div class="min-w-0"><div class="font-semibold text-slate-900 truncate">{{ auth()->user()->name }}</div><div class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</div></div>
          </div>
          <div class="py-1 text-sm">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 text-slate-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>My Profile / Photo</a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('settings.edit') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 text-slate-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.3a1.7 1.7 0 013.4 0 1.7 1.7 0 002.5 1.1 1.7 1.7 0 011.7 2.9 1.7 1.7 0 000 3.4 1.7 1.7 0 01-1.7 2.9 1.7 1.7 0 00-2.5 1.1 1.7 1.7 0 01-3.4 0 1.7 1.7 0 00-2.5-1.1 1.7 1.7 0 01-1.7-2.9 1.7 1.7 0 000-3.4 1.7 1.7 0 011.7-2.9 1.7 1.7 0 002.5-1.1zM12 15a3 3 0 100-6 3 3 0 000 6z"/></svg>Settings</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 mt-1">@csrf
              <button class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 text-red-600 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</button></form>
          </div>
        </div>
      </details>
    </div>
  </div>
</header>

<div class="lg:flex">
  {{-- ============ Sidebar ============ --}}
  <aside class="bg-gradient-to-b from-brand-900 to-brand-950 text-emerald-50 w-64 shrink-0 fixed lg:sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto z-30 -translate-x-full peer-checked:translate-x-0 lg:translate-x-0 transition">
    <nav class="p-3 space-y-1 pt-5">
      @foreach($nav as [$route, $label, $icon])
        @php $active = request()->routeIs(Str::before($route, '.').'*') || request()->routeIs($route); @endphp
        <a href="{{ route($route) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ $active ? 'bg-white/15 text-white font-semibold shadow-inner ring-1 ring-gold/40' : 'text-emerald-100/90 hover:bg-white/10' }}">
          <svg class="w-5 h-5 shrink-0 {{ $active ? 'text-gold-light' : 'text-emerald-300/80 group-hover:text-gold-light' }}" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>{{ $label }}
        </a>
      @endforeach
    </nav>
    <div class="p-4 mt-4 text-[11px] text-emerald-200/50">© {{ date('Y') }} {{ \App\Support\Setting::get('agency_name', 'Gulshan-e-Harmain') }}</div>
  </aside>
  <label for="menu" class="fixed inset-0 top-16 bg-black/40 z-20 hidden peer-checked:block lg:hidden"></label>

  <div class="flex-1 min-w-0">
    <main class="p-4 lg:p-8 space-y-5 max-w-[1600px]">
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">@yield('title', 'Dashboard')</h1>
      <div id="flash" class="hidden" data-ok="{{ session('ok') }}" data-err="{{ session('err') }}" @if($errors->any()) data-invalid="1" @endif></div>
      @yield('content')
    </main>
  </div>
</div>
<script>
  // close the profile dropdown when clicking elsewhere
  document.addEventListener('click', e => { const d = document.getElementById('userMenu'); if (d && !d.contains(e.target)) d.removeAttribute('open'); });
</script>
@stack('scripts')
</body>
</html>
