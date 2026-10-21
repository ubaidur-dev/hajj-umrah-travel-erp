@php $size = $size ?? 'h-11 w-11'; @endphp
@if($user->avatar_url)
  <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="{{ $size }} rounded-full object-cover ring-2 ring-gold shrink-0">
@else
  <span class="{{ $size }} rounded-full bg-gradient-to-br from-brand-500 to-brand-800 text-white font-semibold flex items-center justify-center ring-2 ring-gold shrink-0">{{ $user->initials }}</span>
@endif
