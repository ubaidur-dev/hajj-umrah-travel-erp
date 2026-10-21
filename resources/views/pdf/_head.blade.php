@php
  $name = \App\Support\Setting::get('agency_name', 'Gulshan-e-Harmain');
  $logo = \App\Support\Setting::get('logo');
  $logoPath = agency_logo_path();
  $f = fn ($w) => storage_path("fonts/Inter-$w.ttf");
@endphp
<style>
  @font-face { font-family: 'Inter'; font-weight: normal; src: url('{{ $f(400) }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 600; src: url('{{ $f(600) }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: bold; src: url('{{ $f(700) }}') format('truetype'); }
  @page { margin: 28px 32px; }
  body { font-family: 'Inter', DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
  .brand { background: #0c3a1c; border-bottom: 3px solid #b8893f; color: #fff; padding: 14px 18px; }
  .brand h1 { margin: 0; font-size: 20px; color: #e2c07a; letter-spacing: .5px; }
  .brand small { color: #a7f3d0; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #f0faf1; color: #14562a; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; }
  td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
  .r { text-align: right; } .b { font-weight: bold; }
  .box { border: 1px solid #e2e8f0; padding: 8px 10px; }
  .title { font-size: 16px; font-weight: bold; color: #14562a; margin: 14px 0 6px; }
  .tag { display:inline-block; padding: 2px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; }
  .ok { background:#d1fae5; color:#065f46; } .warn { background:#fef3c7; color:#92400e; } .bad { background:#fee2e2; color:#b91c1c; }
  .foot { margin-top: 24px; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
<div class="brand"><table style="width:100%"><tr>
  <td style="border:0;padding:0;color:#fff">
    <h1>{{ $name }}</h1><small>{{ \App\Support\Setting::get('agency_tagline') }}</small><br>
    <small>{{ \App\Support\Setting::get('address') }}</small><br>
    <small>{{ \App\Support\Setting::get('phone') }}{{ \App\Support\Setting::get('whatsapp') ? ' · WhatsApp: '.\App\Support\Setting::get('whatsapp') : '' }}{{ \App\Support\Setting::get('email') ? ' · '.\App\Support\Setting::get('email') : '' }}</small>
  </td>
  <td style="border:0;padding:0;text-align:right;width:90px"><div style="width:80px;height:80px;border-radius:40px;background:#fff;overflow:hidden;float:right"><img src="{{ $logoPath }}" style="width:80px;height:80px"></div></td>
</tr></table></div>
