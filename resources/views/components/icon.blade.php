@props(['name', 'size' => 20, 'stroke' => 1.7])

<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round"
    aria-hidden="true">
    @switch($name)
        @case('box')
            <path d="M21 8v8a2 2 0 0 1-1 1.73l-7 4a2 2 0 0 1-2 0l-7-4A2 2 0 0 1 3 16V8a2 2 0 0 1 1-1.73l7-4a2 2 0 0 1 2 0l7 4A2 2 0 0 1 21 8z" />
            <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
            <line x1="12" y1="22.08" x2="12" y2="12" />
            @break
        @case('home')
            <path d="M3 9.5 12 3l9 6.5V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 13 15 13 15 22" />
            @break
        @case('mail')
            <rect x="2.5" y="5" width="19" height="14" rx="2.5" />
            <path d="m3.5 7.5 8.5 5.5 8.5-5.5" />
            @break
        @case('lock')
            <rect x="4.5" y="11" width="15" height="9.5" rx="2" />
            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
            @break
        @case('eye')
            <path d="M1.5 12S5.5 4.5 12 4.5 22.5 12 22.5 12 18.5 19.5 12 19.5 1.5 12 1.5 12z" />
            <circle cx="12" cy="12" r="3" />
            @break
        @case('eye-off')
            <path d="M9.9 4.75A10.6 10.6 0 0 1 12 4.5c6.5 0 10.5 7.5 10.5 7.5a17.9 17.9 0 0 1-2.23 3.19M6.6 6.6C3.4 8.6 1.5 12 1.5 12S5.5 19.5 12 19.5c1.8 0 3.4-.5 4.8-1.3" />
            <path d="M9.9 9.9a3 3 0 0 0 4.24 4.24" />
            <line x1="2" y1="2" x2="22" y2="22" />
            @break
        @case('arrow-right')
            <line x1="4.5" y1="12" x2="19.5" y2="12" />
            <polyline points="13 5.5 19.5 12 13 18.5" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="7.5" />
            <line x1="20.5" y1="20.5" x2="16.4" y2="16.4" />
            @break
        @case('plus')
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
            @break
        @case('calendar')
            <rect x="3.5" y="4.5" width="17" height="16" rx="2" />
            <line x1="16" y1="2.5" x2="16" y2="6.5" />
            <line x1="8" y1="2.5" x2="8" y2="6.5" />
            <line x1="3.5" y1="10" x2="20.5" y2="10" />
            @break
        @case('cog')
            <circle cx="12" cy="12" r="3.2" />
            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.03 1.56V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 9 19.36a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.56-1.03H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.64 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34H9a1.7 1.7 0 0 0 1.03-1.56V3a2 2 0 1 1 4 0v.09c0 .68.4 1.3 1.03 1.56a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87V9c.26.63.88 1.03 1.56 1.03H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.56 1.03z" />
            @break
        @case('bell')
            <path d="M18 8.5a6 6 0 1 0-12 0c0 7-2.5 8.5-2.5 8.5h17S18 15.5 18 8.5" />
            <path d="M13.7 20.5a2 2 0 0 1-3.4 0" />
            @break
        @case('check')
            <polyline points="4.5 12.5 9.5 17.5 19.5 6.5" />
            @break
        @case('x')
            <line x1="6" y1="6" x2="18" y2="18" />
            <line x1="18" y1="6" x2="6" y2="18" />
            @break
        @case('chevron-left')
            <polyline points="14.5 6 8.5 12 14.5 18" />
            @break
        @case('chevron-right')
            <polyline points="9.5 6 15.5 12 9.5 18" />
            @break
        @case('chevron-down')
            <polyline points="6 9.5 12 15.5 18 9.5" />
            @break
        @case('sun')
            <circle cx="12" cy="12" r="4.5" />
            <path d="M12 2v2.5M12 19.5V22M4.93 4.93l1.77 1.77M17.3 17.3l1.77 1.77M2 12h2.5M19.5 12H22M4.93 19.07l1.77-1.77M17.3 6.7l1.77-1.77" />
            @break
        @case('moon')
            <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
            @break
        @case('hand')
            <path d="M18 12.5V6.75a1.75 1.75 0 0 0-3.5 0m0 5V4.75a1.75 1.75 0 0 0-3.5 0v6.5m0 .25V6.75a1.75 1.75 0 0 0-3.5 0V14" />
            <path d="M18 8.5a1.75 1.75 0 0 1 3.5 0V14a8 8 0 0 1-8 8h-1.5c-2.9 0-4.7-1.2-6.2-3.3l-2.5-3.6a1.8 1.8 0 0 1 2.9-2.1L7.5 15" />
            @break
        @case('list')
            <line x1="9" y1="6" x2="20.5" y2="6" />
            <line x1="9" y1="12" x2="20.5" y2="12" />
            <line x1="9" y1="18" x2="20.5" y2="18" />
            <circle cx="4.5" cy="6" r="0.5" fill="currentColor" />
            <circle cx="4.5" cy="12" r="0.5" fill="currentColor" />
            <circle cx="4.5" cy="18" r="0.5" fill="currentColor" />
            @break
        @case('map-pin')
            <path d="M20 10.5c0 5.5-8 11-8 11s-8-5.5-8-11a8 8 0 0 1 16 0z" />
            <circle cx="12" cy="10.5" r="3" />
            @break
        @case('log-out')
            <path d="M9 21H5.5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2H9" />
            <polyline points="16 16.5 20.5 12 16 7.5" />
            <line x1="20.5" y1="12" x2="9" y2="12" />
            @break
        @case('wrench')
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z" />
            @break
        @case('bolt')
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10" />
            @break
        @case('globe')
            <circle cx="12" cy="12" r="9.5" />
            <line x1="2.5" y1="12" x2="21.5" y2="12" />
            <path d="M12 2.5a14.5 14.5 0 0 1 0 19a14.5 14.5 0 0 1 0-19z" />
            @break
        @case('doc')
            <path d="M14 2.5H6.5a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V8z" />
            <polyline points="14 2.5 14 8 19.5 8" />
            <line x1="9" y1="13" x2="15" y2="13" />
            <line x1="9" y1="17" x2="13" y2="17" />
            @break
        @case('utensil')
            <path d="M4 2.5v6a2.5 2.5 0 0 0 5 0v-6M6.5 2.5V21" />
            <path d="M18.5 15h-3.75a0.5 0.5 0 0 1-0.5-0.55C14.55 10 15.5 2.5 18.5 2.5V21" />
            @break
        @case('shirt')
            <path d="M20.4 6.5 16.5 4a4.5 4.5 0 0 1-9 0L3.6 6.5a1 1 0 0 0-.4 1.3l1.6 3a1 1 0 0 0 1.3.45L7.5 10.5V20a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1v-9.5l1.4.75a1 1 0 0 0 1.3-.45l1.6-3a1 1 0 0 0-.4-1.3z" />
            @break
        @case('drill')
            <path d="M3 6.5h11a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2H3a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1z" />
            <path d="M16 8h4.5a1 1 0 0 1 1 1v0a1 1 0 0 1-1 1H16M5.5 11.5 4.75 17a1 1 0 0 0 1 1.15h3.5a1 1 0 0 0 1-.85l.65-5.8" />
            @break
        @case('sliders')
            <line x1="4" y1="7" x2="20" y2="7" />
            <line x1="4" y1="17" x2="20" y2="17" />
            <circle cx="9.5" cy="7" r="2.4" fill="var(--surface)" />
            <circle cx="14.5" cy="17" r="2.4" fill="var(--surface)" />
            @break
        @case('filter')
            <polygon points="21 4 3 4 10 12.5 10 19 14 21 14 12.5" />
            @break
        @case('dots')
            <circle cx="5" cy="12" r="1" fill="currentColor" />
            <circle cx="12" cy="12" r="1" fill="currentColor" />
            <circle cx="19" cy="12" r="1" fill="currentColor" />
            @break
        @case('edit')
            <path d="M11 4.5H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h12.5a2 2 0 0 0 2-2v-6" />
            <path d="M17.8 3.3a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z" />
            @break
        @case('camera')
            <path d="M22 18.5a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-10a2 2 0 0 1 2-2h3l2-3h6l2 3h3a2 2 0 0 1 2 2z" />
            <circle cx="12" cy="13" r="3.6" />
            @break
        @case('star')
            <polygon points="12 2.5 15 8.8 22 9.8 17 14.6 18.2 21.5 12 18.2 5.8 21.5 7 14.6 2 9.8 9 8.8" />
            @break
        @case('tag')
            <path d="M20.6 13.4 12.6 21.4a2 2 0 0 1-2.8 0l-7.2-7.2a1 1 0 0 1-.3-.7V4a1 1 0 0 1 1-1h9.5a1 1 0 0 1 .7.3l7.1 7.1a2 2 0 0 1 0 3z" />
            <circle cx="7.5" cy="7.5" r="1.2" fill="currentColor" />
            @break
        @case('layers')
            <polygon points="12 2.5 22 8 12 13.5 2 8" />
            <polyline points="2 12.5 12 18 22 12.5" />
            <polyline points="2 17 12 22.5 22 17" />
            @break
        @case('cube')
            <path d="M21 8v8a2 2 0 0 1-1 1.73l-7 4a2 2 0 0 1-2 0l-7-4A2 2 0 0 1 3 16V8a2 2 0 0 1 1-1.73l7-4a2 2 0 0 1 2 0l7 4A2 2 0 0 1 21 8z" />
            <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
            <line x1="12" y1="22.08" x2="12" y2="12" />
            @break
        @case('shield')
            <path d="M12 22.5s8-4 8-10V5l-8-2.5L4 5v7.5c0 6 8 10 8 10z" />
            @break
        @case('hash')
            <line x1="4" y1="9" x2="20" y2="9" />
            <line x1="4" y1="15" x2="20" y2="15" />
            <line x1="10" y1="3" x2="8" y2="21" />
            <line x1="16" y1="3" x2="14" y2="21" />
            @break
        @case('undo')
            <polyline points="9 14 4 9 9 4" />
            <path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11" />
            @break
        @case('check-circle')
            <circle cx="12" cy="12" r="9.5" />
            <polyline points="8 12.5 11 15.5 16.5 9" />
            @break
        @case('user')
            <path d="M19.5 21v-2a4 4 0 0 0-4-4h-7a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7.5" r="4" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9.5" />
            <polyline points="12 6.5 12 12 15.5 14" />
            @break
        @case('trash')
            <polyline points="3.5 6.5 20.5 6.5" />
            <path d="M8.5 6.5V4.75a1.25 1.25 0 0 1 1.25-1.25h4.5a1.25 1.25 0 0 1 1.25 1.25V6.5m3 0V19a2 2 0 0 1-2 2H7.5a2 2 0 0 1-2-2V6.5" />
            <line x1="10" y1="11" x2="10" y2="17" />
            <line x1="14" y1="11" x2="14" y2="17" />
            @break
    @endswitch
</svg>
