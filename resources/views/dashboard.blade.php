@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
  $first = Str::before(auth()->user()->name, ' ');
  $greet = now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening');
  $types = \App\Http\Controllers\PackageController::TYPES;
  $methods = \App\Models\Payment::METHODS;
  $statusColors = ['pending' => '#f59e0b', 'confirmed' => '#1a6e2f', 'completed' => '#0284c7', 'cancelled' => '#ef4444'];
  $net = $stats['net_profit'];
  $maxService = max(1, (float) $byService->max('s'));
  $maxPkg = max(1, (float) $topPackages->max('revenue'));
  $totalBookings = max(1, $byStatus->sum());
  $d = 0; $next = function () use (&$d) { return 'style="--d:'.($d += 60).'ms"'; };
@endphp

<div class="rise flex flex-wrap items-end justify-between gap-3" {!! $next() !!}>
  <div class="space-y-1"><div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ now()->format('l, d F Y') }}</div>
    <div class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 leading-tight">{{ $greet }}, {{ $first }} <span class="inline-block origin-[70%_70%] animate-[wave_2.2s_ease-in-out_1]">👋</span></div>
    <div class="text-sm text-slate-500">Here is how {{ \App\Support\Setting::get('agency_name', 'your agency') }} is doing.</div></div>
  <div class="inline-flex items-center gap-2 rounded-full bg-white border border-slate-200 px-3 py-1.5 text-xs text-slate-600 shadow-sm">
    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full rounded-full bg-brand-500 opacity-60 animate-ping"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-brand-500"></span></span>
    Live · <span id="updated">updated just now</span></div>
</div>


<div id="live" class="space-y-6">

{{-- Attention --}}
@if(array_sum($attention))
<div class="rise flex flex-wrap gap-2.5" {!! $next() !!}>
  @if($attention['overdue'])<a href="{{ route('bookings.index', ['overdue' => 1]) }}" class="lift inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700"><b>{{ $attention['overdue'] }}</b> overdue {{ Str::plural('payment', $attention['overdue']) }}</a>@endif
  @if($attention['followups'])<a href="{{ route('leads.index', ['due' => 1]) }}" class="lift inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800"><b>{{ $attention['followups'] }}</b> {{ Str::plural('lead', $attention['followups']) }} to follow up</a>@endif
  @if($attention['visa'])<span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800"><b>{{ $attention['visa'] }}</b> without visa, leaving within 30 days</span>@endif
  @if($attention['passports'])<span class="inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700"><b>{{ $attention['passports'] }}</b> {{ Str::plural('passport', $attention['passports']) }} valid under 6 months</span>@endif
</div>
@endif

{{-- KPI cards with sparklines --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
  @php $kpis = [
    ['Total sales', $stats['sales'], $deltas['sales'], $series['sales'], '#1a6e2f', 'M3 17l6-6 4 4 8-8M14 7h7v7'],
    ['Gross profit', $stats['gross_profit'], $deltas['profit'], $series['profit'], '#b8893f', 'M12 8c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['Cash received', $stats['received'], $deltas['received'], $series['received'], '#0284c7', 'M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
  ]; @endphp
  @foreach($kpis as [$label, $val, $dl, $spark, $color, $icon])
  <div class="rise lift card card-b !p-5 relative overflow-hidden" {!! $next() !!}>
    <div class="flex items-start justify-between">
      <div><div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</div>
        <div class="mt-2 text-[1.4rem] whitespace-nowrap leading-none font-extrabold text-slate-900" data-money="{{ $val }}">{{ money($val) }}</div></div>
      <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: {{ $color }}1a; color: {{ $color }}"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg></span>
    </div>
    <div class="mt-3 flex items-center gap-2 text-xs">
      @if($dl === null)<span class="text-slate-400">No data for last month yet</span>
      @else<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold {{ $dl >= 0 ? 'bg-brand-50 text-brand-700' : 'bg-red-50 text-red-600' }}">{{ $dl >= 0 ? '▲' : '▼' }} {{ abs($dl) }}%</span><span class="text-slate-500">vs last month</span>@endif
    </div>
    <div class="mt-3 -mx-1 h-12"><canvas data-spark data-series='@json($spark)' data-color="{{ $color }}"></canvas></div>
  </div>
  @endforeach
  <div class="rise lift card card-b !p-5" {!! $next() !!}>
    <div class="flex items-start justify-between">
      <div><div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Outstanding</div>
        <div class="mt-2 text-[1.4rem] whitespace-nowrap leading-none font-extrabold text-red-600" data-money="{{ $stats['receivable'] }}">{{ money($stats['receivable']) }}</div></div>
      <span class="h-10 w-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
    </div>
    <div class="mt-3 text-xs text-slate-500">still to collect from customers</div>
    <div class="mt-4"><div class="flex justify-between text-xs mb-1.5"><span class="text-slate-500">Collected</span><b class="text-slate-800">{{ $rings['collected'] }}%</b></div>
      <div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="bar-grow h-full rounded-full bg-gradient-to-r from-brand-700 to-brand-500" style="width: {{ $rings['collected'] }}%"></div></div></div>
    <div class="mt-3 text-xs text-slate-500">{{ $stats['pending'] }} pending {{ Str::plural('order', $stats['pending']) }} waiting for confirmation</div>
  </div>
</div>

{{-- Performance rings --}}
<div class="rise card" {!! $next() !!}>
  <div class="card-h"><div>Performance<div class="card-sub">How healthy the business is, at a glance</div></div></div>
  <div class="card-b grid grid-cols-2 lg:grid-cols-4 gap-6">
    @foreach([
      ['Profit margin', $rings['margin'], '#1a6e2f', '#4ade80', money($stats['gross_profit']), 'gross profit'],
      ['Money collected', $rings['collected'], '#b8893f', '#f5d37a', money($rings['paid_on_sales']), 'received from sales'],
      ['Cost share', $rings['cost'], '#475569', '#94a3b8', money($stats['cost']), 'package cost'],
      ['Seats filled', $rings['seats'], '#0284c7', '#7dd3fc', $rings['seats_booked'].' / '.$rings['seats_total'], 'upcoming seats'],
    ] as [$label, $pct, $c1, $c2, $big, $small])
    <div class="flex flex-col items-center text-center gap-3">
      <div class="relative h-32 w-32"><canvas data-ring data-value="{{ $pct }}" data-from="{{ $c1 }}" data-to="{{ $c2 }}"></canvas>
        <div class="absolute inset-0 flex items-center justify-center text-2xl font-extrabold text-slate-900"><span data-count="{{ $pct }}" data-suffix="%" data-dec="1">{{ $pct }}%</span></div></div>
      <div><div class="text-sm font-semibold text-slate-900">{{ $label }}</div><div class="text-xs text-slate-500">{{ $big }} · {{ $small }}</div></div>
    </div>
    @endforeach
  </div>
</div>

{{-- Trend + P&L --}}
<div class="grid lg:grid-cols-3 gap-5">
  <div class="rise card lg:col-span-2" {!! $next() !!}><div class="card-h"><div>Revenue trend<div class="card-sub">Sales, profit and cash received over 6 months</div></div></div>
    <div class="card-b"><div class="relative h-72"><canvas id="chart"></canvas></div></div></div>
  <div class="rise card" {!! $next() !!}><div class="card-h"><div>Profit &amp; loss<div class="card-sub">Where every rupee of sales goes</div></div></div>
    <div class="card-b flex flex-col items-center gap-5">
      <div class="relative h-56 w-56"><canvas id="plChart"></canvas>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
          <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $net >= 0 ? 'Net profit' : 'Net loss' }}</div>
          <div class="text-base font-extrabold whitespace-nowrap {{ $net >= 0 ? 'text-brand-700' : 'text-red-600' }}" data-money="{{ abs($net) }}">{{ money(abs($net)) }}</div></div></div>
      <div class="w-full space-y-2 text-sm">
        <div class="flex justify-between"><span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-slate-600"></i>Package cost</span><b>{{ money($stats['cost']) }}</b></div>
        <div class="flex justify-between"><span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-gold"></i>Office expenses</span><b>{{ money($stats['expenses']) }}</b></div>
        <div class="flex justify-between border-t border-slate-100 pt-2"><span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-brand-700"></i>{{ $net >= 0 ? 'Net profit' : 'Net loss' }}</span><b class="{{ $net < 0 ? 'text-red-600' : 'text-brand-700' }}">{{ $net < 0 ? '− ' : '' }}{{ money(abs($net)) }}</b></div>
      </div></div></div>
</div>

{{-- Breakdowns --}}
<div class="grid lg:grid-cols-3 gap-5">
  <div class="rise card" {!! $next() !!}><div class="card-h">Sales by service</div><div class="card-b space-y-4">
    @forelse($byService as $r)
      <div><div class="flex justify-between text-sm mb-1.5"><span class="font-medium text-slate-800">{{ $types[$r->service_type] ?? ucfirst($r->service_type) }} <span class="text-slate-400 font-normal">· {{ $r->c }}</span></span><b>{{ money($r->s) }}</b></div>
        <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden"><div class="bar-grow h-full rounded-full bg-gradient-to-r from-brand-700 to-brand-500" style="width: {{ max(4, $r->s / $maxService * 100) }}%"></div></div></div>
    @empty<p class="text-sm text-slate-500">Confirmed bookings will appear here.</p>@endforelse
  </div></div>

  <div class="rise card" {!! $next() !!}><div class="card-h">Bookings by status</div><div class="card-b space-y-4">
    <div class="flex h-3 rounded-full overflow-hidden bg-slate-100">@foreach(\App\Models\Booking::STATUSES as $k => $label)@if(($byStatus[$k] ?? 0) > 0)<div class="bar-grow h-full" style="width: {{ $byStatus[$k] / $totalBookings * 100 }}%; background: {{ $statusColors[$k] }}"></div>@endif @endforeach</div>
    <div class="grid grid-cols-2 gap-3">@foreach(\App\Models\Booking::STATUSES as $k => $label)
      <a href="{{ route('bookings.index', ['status' => $k]) }}" class="rounded-xl border border-slate-100 px-3 py-2.5 hover:bg-slate-50 transition"><div class="flex items-center gap-2 text-xs text-slate-500"><i class="h-2 w-2 rounded-full" style="background: {{ $statusColors[$k] }}"></i>{{ $label }}</div><div class="text-xl font-bold text-slate-900 mt-0.5" data-count="{{ $byStatus[$k] ?? 0 }}">{{ $byStatus[$k] ?? 0 }}</div></a>
    @endforeach</div></div></div>

  <div class="rise card" {!! $next() !!}><div class="card-h">Payments by method</div><div class="card-b">
    @if($byMethod->sum() > 0)
    <div class="flex items-center gap-5"><div class="relative h-32 w-32 shrink-0"><canvas id="methodChart" data-labels='@json($byMethod->keys()->map(fn($k) => $methods[$k] ?? ucfirst($k))->values())' data-values='@json($byMethod->values()->map(fn($v) => (float) $v))'></canvas></div>
      <div class="space-y-1.5 text-sm min-w-0">@foreach($byMethod as $k => $v)<div class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full shrink-0 method-dot" data-i="{{ $loop->index }}"></i><span class="truncate">{{ $methods[$k] ?? ucfirst($k) }}</span></div>@endforeach</div></div>
    @else<p class="text-sm text-slate-500">Payments will appear here.</p>@endif
  </div></div>
</div>

{{-- Operational lists --}}
<div class="grid lg:grid-cols-3 gap-5">
  <div class="rise card" {!! $next() !!}><div class="card-h">Recent bookings <a class="text-xs font-medium text-brand-700" href="{{ route('bookings.index') }}">View all →</a></div>
    <div class="divide-y divide-slate-100">
    @forelse($recent as $b)
      <a href="{{ (!empty($demo) ? route('bookings.create') : route('bookings.show', $b)) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition">
        <span class="h-9 w-9 shrink-0 rounded-full bg-gradient-to-br from-brand-500 to-brand-800 text-white text-xs font-bold flex items-center justify-center">{{ collect(explode(' ', $b->customer->name))->take(2)->map(fn($w) => mb_strtoupper(mb_substr($w,0,1)))->implode('') }}</span>
        <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-slate-900 truncate">{{ $b->customer->name }}</span><span class="block text-xs text-slate-500 truncate">{{ $b->booking_no }} · {{ $b->title }}</span></span>
        <span class="text-right"><span class="block text-sm font-semibold">{{ money($b->net_total) }}</span><x-badge :status="$b->status" /></span></a>
    @empty<p class="px-5 py-8 text-sm text-slate-500 text-center">No bookings yet.</p>@endforelse
    </div></div>

  <div class="rise card" {!! $next() !!}><div class="card-h">Outstanding payments <a class="text-xs font-medium text-brand-700" href="{{ route('bookings.index', ['due' => 1]) }}">View all →</a></div>
    <div class="divide-y divide-slate-100">
    @forelse($dues as $b)
      @php $pct = $b->net_total > 0 ? round($b->paid_total / $b->net_total * 100) : 0; @endphp
      <a href="{{ (!empty($demo) ? route('bookings.create') : route('bookings.show', $b)) }}" class="block px-5 py-3 hover:bg-slate-50 transition">
        <div class="flex justify-between text-sm"><span class="font-semibold text-slate-900 truncate">{{ $b->customer->name }}</span><b class="text-red-600 shrink-0">{{ money($b->due) }}</b></div>
        <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="bar-grow h-full rounded-full {{ $b->is_overdue ? 'bg-red-500' : 'bg-brand-500' }}" style="width: {{ max(3, $pct) }}%"></div></div>
        <div class="mt-1 flex justify-between text-[11px] text-slate-500"><span>{{ $b->booking_no }} · {{ $pct }}% paid</span><span class="{{ $b->is_overdue ? 'text-red-600 font-semibold' : '' }}">{{ $b->payment_due_date ? 'due '.dt($b->payment_due_date, 'd M') : '' }}</span></div></a>
    @empty<p class="px-5 py-8 text-sm text-slate-500 text-center">Nothing outstanding 🎉</p>@endforelse
    </div></div>

  <div class="rise card" {!! $next() !!}><div class="card-h">Upcoming trips <span class="text-xs font-normal text-slate-500">next 30 days</span></div>
    <div class="divide-y divide-slate-100">
    @forelse($upcoming as $b)
      @php $days = (int) today()->diffInDays($b->travel_date, false); @endphp
      <a href="{{ (!empty($demo) ? route('bookings.create') : route('bookings.show', $b)) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition">
        <span class="h-11 w-11 shrink-0 rounded-xl bg-brand-50 text-brand-800 flex flex-col items-center justify-center leading-none"><b class="text-base">{{ $b->travel_date->format('d') }}</b><span class="text-[10px] uppercase">{{ $b->travel_date->format('M') }}</span></span>
        <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-slate-900 truncate">{{ $b->customer->name }}</span><span class="block text-xs text-slate-500 truncate">{{ $b->title }} · {{ $b->pax }} pax</span></span>
        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $days <= 7 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">{{ $days === 0 ? 'Today' : 'in '.$days.'d' }}</span></a>
    @empty<p class="px-5 py-8 text-sm text-slate-500 text-center">No trips in the next 30 days.</p>@endforelse
    </div></div>
</div>

<div class="grid lg:grid-cols-3 gap-5">
  <div class="rise card lg:col-span-2" {!! $next() !!}><div class="card-h">Top packages</div><div class="card-b space-y-4">
    @forelse($topPackages as $i => $p)
      <a href="{{ (!empty($demo) ? route('packages.create') : route('packages.show', $p)) }}" class="block group"><div class="flex justify-between text-sm mb-1.5"><span class="font-medium text-slate-800 group-hover:text-brand-700"><span class="text-slate-400 mr-1">{{ $i+1 }}.</span>{{ $p->name }}</span><span class="text-slate-500"><b class="text-slate-900">{{ $p->sold }}</b> sold · {{ money($p->revenue ?? 0) }}</span></div>
        <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden"><div class="bar-grow h-full rounded-full bg-gradient-to-r from-gold to-gold-light" style="width: {{ max(4, ($p->revenue ?? 0) / $maxPkg * 100) }}%"></div></div></a>
    @empty<p class="text-sm text-slate-500">Create a package to see how it sells.</p>@endforelse
  </div></div>

  <div class="rise card" {!! $next() !!}><div class="card-h">Lead follow-ups <a class="text-xs font-medium text-brand-700" href="{{ route('leads.index') }}">View all →</a></div>
    <div class="divide-y divide-slate-100">
    @forelse($followups as $l)
      <a href="{{ (!empty($demo) ? route('leads.create') : route('leads.edit', $l)) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50 transition">
        <span class="min-w-0"><span class="block text-sm font-semibold text-slate-900 truncate">{{ $l->name }}</span><span class="block text-xs text-slate-500">{{ \App\Http\Controllers\LeadController::INTERESTS[$l->interest] ?? $l->interest }}{{ $l->persons ? ' · '.$l->persons.' persons' : '' }}</span></span>
        <span class="text-xs font-semibold {{ $l->is_due ? 'text-red-600' : 'text-slate-500' }}">{{ $l->is_due ? 'Due ' : '' }}{{ dt($l->next_follow_up, 'd M') }}</span></a>
    @empty<p class="px-5 py-8 text-sm text-slate-500 text-center">No follow-ups scheduled.</p>@endforelse
    </div></div>
</div>

{{-- Overview counters --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
  @foreach($overview as [$label, $n, $icon, $url])
  <a href="{{ $url }}" class="rise lift card px-4 py-4 flex items-center gap-3" {!! $next() !!}>
    <span class="h-10 w-10 shrink-0 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg></span>
    <span><span class="block text-xl font-extrabold text-slate-900 leading-none" data-count="{{ $n }}">{{ $n }}</span><span class="block text-xs text-slate-500 mt-1">{{ $label }}</span></span></a>
  @endforeach
</div>

<script type="application/json" id="chartData">@json($chart)</script>
</div>
@endsection
@push('scripts')
<script>
const charts = [];
const fmt = (n, dec = 0) => Number(n).toLocaleString('en-US', {minimumFractionDigits: dec, maximumFractionDigits: dec});
const EMPTY = false;
const GHOST = [18, 34, 26, 48, 40, 62];
const PALETTE = ['#1a6e2f', '#b8893f', '#0284c7', '#475569', '#e11d48', '#7c3aed'];

function countUp(animate) {
  document.querySelectorAll('[data-count],[data-money]').forEach(el => {
    const money = el.hasAttribute('data-money');
    const target = parseFloat(el.dataset.money ?? el.dataset.count) || 0;
    const dec = parseInt(el.dataset.dec || 0), suffix = el.dataset.suffix || '', prefix = money ? 'PKR ' : '';
    const show = v => el.textContent = prefix + fmt(v, dec) + suffix;
    if (!animate) return show(target);
    const t0 = performance.now(), dur = 1400;
    const step = now => { const k = Math.min(1, (now - t0) / dur), e = 1 - Math.pow(1 - k, 4); show(target * e); if (k < 1) requestAnimationFrame(step); };
    requestAnimationFrame(step);
  });
}

function gradient(ctx, area, from, to, alpha = 1) {
  const g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
  g.addColorStop(0, from + (alpha < 1 ? '55' : '')); g.addColorStop(1, to + (alpha < 1 ? '00' : ''));
  return g;
}

function draw(animate) {
  charts.splice(0).forEach(c => c.destroy());
  const data = JSON.parse(document.getElementById('chartData').textContent);
  const anim = animate ? {duration: 1400, easing: 'easeOutQuart'} : false;

  // sparklines inside the KPI cards
  document.querySelectorAll('canvas[data-spark]').forEach(cv => {
    const ghost = cv.dataset.ghost === '1';
    const s = ghost ? GHOST : JSON.parse(cv.dataset.series), color = cv.dataset.color;
    charts.push(new Chart(cv, {type: 'line', data: {labels: s.map((_, i) => i), datasets: [{data: s, borderColor: ghost ? color + '66' : color, borderDash: ghost ? [5, 5] : [], borderWidth: 2.5, tension: .4, pointRadius: 0, fill: true,
      backgroundColor: ctx => ctx.chart.chartArea ? gradient(ctx.chart.ctx, ctx.chart.chartArea, color, color, .5) : 'transparent'}]},
      options: {maintainAspectRatio: false, animation: anim, plugins: {legend: {display: false}, tooltip: {enabled: false}}, scales: {x: {display: false}, y: {display: false, beginAtZero: true}}, events: []}}));
  });

  // rings with gradient strokes
  document.querySelectorAll('canvas[data-ring]').forEach(cv => {
    const v = Math.max(0, Math.min(100, parseFloat(cv.dataset.value) || 0)), a = cv.dataset.from, b = cv.dataset.to;
    charts.push(new Chart(cv, {type: 'doughnut', data: {datasets: [{data: [v, 100 - v], borderWidth: 0, borderRadius: 10,
      backgroundColor: ctx => { if (ctx.dataIndex === 1) return '#eef2f1'; const c = ctx.chart; if (!c.chartArea) return a; const g = c.ctx.createLinearGradient(0, c.chartArea.top, c.chartArea.right, c.chartArea.bottom); g.addColorStop(0, a); g.addColorStop(1, b); return g; }}]},
      options: {cutout: '80%', animation: anim, plugins: {legend: {display: false}, tooltip: {enabled: false}}, events: []}}));
  });

  // profit & loss donut
  const pl = data.pl, empty = pl.cost + pl.exp + pl.profit === 0;
  charts.push(new Chart(document.getElementById('plChart'), {type: 'doughnut',
    data: {labels: ['Package cost', 'Office expenses', 'Net profit'], datasets: [{data: empty ? [1] : [pl.cost, pl.exp, pl.profit], backgroundColor: empty ? ['#eef2f1'] : ['#475569', '#b8893f', '#1a6e2f'], borderWidth: 4, borderColor: '#fff', borderRadius: 8, hoverOffset: 6}]},
    options: {cutout: '78%', animation: anim, plugins: {legend: {display: false}, tooltip: {enabled: !empty, callbacks: {label: i => ' ' + i.label + ': PKR ' + fmt(i.raw)}}}}}));

  // revenue trend (soft sample curves while there is no data yet)
  if (EMPTY) { data.sales = [8, 22, 18, 36, 30, 52].map(v => v * 1e4); data.profit = [2, 6, 5, 10, 9, 16].map(v => v * 1e4); data.received = [5, 14, 12, 26, 22, 40].map(v => v * 1e4); }
  const line = (label, arr, color) => ({label, data: arr, borderColor: EMPTY ? color + '55' : color, borderDash: EMPTY ? [6, 6] : [], borderWidth: 3, tension: .42, fill: true, pointRadius: 0, pointHoverRadius: 6, pointHoverBackgroundColor: '#fff', pointHoverBorderColor: color, pointHoverBorderWidth: 3,
    backgroundColor: ctx => ctx.chart.chartArea ? gradient(ctx.chart.ctx, ctx.chart.chartArea, color, color, .5) : 'transparent'});
  charts.push(new Chart(document.getElementById('chart'), {type: 'line',
    data: {labels: data.labels, datasets: [line('Sales', data.sales, '#1a6e2f'), line('Profit', data.profit, '#b8893f'), line('Cash received', data.received, '#0284c7')]},
    options: {maintainAspectRatio: false, animation: anim, interaction: {mode: 'index', intersect: false},
      plugins: {legend: {position: 'bottom', labels: {usePointStyle: true, pointStyle: 'circle', padding: 18}}, tooltip: {enabled: !EMPTY, backgroundColor: '#0f172a', padding: 12, cornerRadius: 10, callbacks: {label: i => ' ' + i.dataset.label + ': PKR ' + fmt(i.raw)}}},
      scales: {y: {beginAtZero: true, border: {display: false}, grid: {color: '#f1f5f9'}, ticks: {callback: v => v >= 1e6 ? (v / 1e6) + 'M' : v >= 1e3 ? (v / 1e3) + 'k' : v}}, x: {grid: {display: false}, border: {display: false}}}}}));

  // payment methods
  const mc = document.getElementById('methodChart');
  if (mc) {
    const vals = JSON.parse(mc.dataset.values), labels = JSON.parse(mc.dataset.labels);
    charts.push(new Chart(mc, {type: 'doughnut', data: {labels, datasets: [{data: vals, backgroundColor: PALETTE, borderWidth: 3, borderColor: '#fff', borderRadius: 6}]},
      options: {cutout: '66%', animation: anim, plugins: {legend: {display: false}, tooltip: {callbacks: {label: i => ' ' + i.label + ': PKR ' + fmt(i.raw)}}}}}));
    document.querySelectorAll('.method-dot').forEach(d => d.style.background = PALETTE[d.dataset.i % PALETTE.length]);
  }
}

// app.js is a deferred module, so wait until Chart.js is ready
window.addEventListener('DOMContentLoaded', () => {
  draw(true); countUp(true);
  setTimeout(() => document.getElementById('live').classList.add('settled'), 2200); // no entrance animation on later refreshes
});
setInterval(async () => {
  if (document.hidden) return;
  try {
    const html = await (await fetch(location.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}})).text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    document.getElementById('live').innerHTML = doc.getElementById('live').innerHTML;
    draw(false); countUp(false);
    document.getElementById('updated').textContent = 'updated ' + new Date().toLocaleTimeString();
  } catch (e) {}
}, 30000);
</script>
@endpush
