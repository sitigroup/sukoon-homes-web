#!/usr/bin/env python3
from pathlib import Path

p = Path('/www/wwwroot/admin-homes/resources/views/layouts/sidebar.blade.php')
text = p.read_text()
needle = '{{-- SEO settings --}}'
insert = """{{-- SEO Engine plugin --}}
                            @if (
                                has_permissions('dashboard', 'seo_engine') ||
                                has_permissions('settings', 'seo_engine')
                            )
                                <li class="submenu-item">
                                    <a href="{{ route('seo-engine.dashboard') }}">{{ __('SEO Engine') }}</a>
                                </li>
                            @endif

                            {{-- SEO settings --}}"""

if "route('seo-engine.dashboard')" in text:
    print('already present')
elif needle not in text:
    raise SystemExit('needle missing')
else:
    p.write_text(text.replace(needle, insert, 1))
    print('sidebar patched')
