@props(['label', 'value', 'sub' => null, 'tone' => 'slate'])
@php $tones = ['slate'=>'text-slate-900','green'=>'text-brand-700','red'=>'text-red-600','gold'=>'text-gold','blue'=>'text-sky-700']; @endphp
<div class="card card-b">
  <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
  <div class="text-2xl font-bold mt-1 {{ $tones[$tone] ?? $tones['slate'] }}">{{ $value }}</div>
  @if($sub)<div class="text-xs text-slate-500 mt-1">{{ $sub }}</div>@endif
</div>
