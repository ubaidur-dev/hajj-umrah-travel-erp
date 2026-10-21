@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'options' => null, 'placeholder' => null, 'rows' => 3, 'hint' => null])
@php $val = old($name, $value); @endphp
<div {{ $attributes->only('class') }}>
  @if($label)<label class="lbl" for="f_{{ $name }}">{{ $label }}@if($required) <span class="text-red-500">*</span>@endif</label>@endif
  @if($type === 'select')
    <select id="f_{{ $name }}" name="{{ $name }}" class="inp" @required($required) {{ $attributes->except('class') }}>
      @if($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
      @foreach($options as $k => $v)<option value="{{ $k }}" @selected((string)$val === (string)$k)>{{ $v }}</option>@endforeach
    </select>
  @elseif($type === 'textarea')
    <textarea id="f_{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" class="inp" placeholder="{{ $placeholder }}" {{ $attributes->except('class') }}>{{ $val }}</textarea>
  @else
    <input id="f_{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $val }}" class="inp" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except('class') }}>
  @endif
  @if($hint)<p class="hint">{{ $hint }}</p>@endif
  @error($name)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
