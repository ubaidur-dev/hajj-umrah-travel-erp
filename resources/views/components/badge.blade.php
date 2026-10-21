@props(['status'])
@php
$map = [
  'pending'=>'bg-amber-100 text-amber-800','confirmed'=>'bg-brand-100 text-brand-800','completed'=>'bg-sky-100 text-sky-800','cancelled'=>'bg-red-100 text-red-700',
  'unpaid'=>'bg-red-100 text-red-700','partial'=>'bg-amber-100 text-amber-800','paid'=>'bg-brand-100 text-brand-800',
];
@endphp
<span {{ $attributes->class(['badge', $map[$status] ?? 'bg-slate-100 text-slate-700']) }}>{{ ucfirst($status) }}</span>
