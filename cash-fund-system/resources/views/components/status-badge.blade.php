@props(['status'])

@php
    $statusMap = [
        'DRAFT'     => ['label' => 'مسودة',   'bg' => 'bg-muted/20',        'text' => 'text-muted',     'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
        'PENDING'   => ['label' => 'قيد المراجعة', 'bg' => 'bg-accent/15',    'text' => 'text-accent',    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        'APPROVED'  => ['label' => 'موافق عليه',   'bg' => 'bg-primary/15',   'text' => 'text-primary',   'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'REJECTED'  => ['label' => 'مرفوض',       'bg' => 'bg-accent/15',    'text' => 'text-accent',    'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'EXECUTED'  => ['label' => 'تم التنفيذ',  'bg' => 'bg-primary/15',   'text' => 'text-primary',   'icon' => 'M5 13l4 4L19 7'],
        'CANCELLED' => ['label' => 'ملغى',        'bg' => 'bg-muted/20',      'text' => 'text-muted',     'icon' => 'M6 18L18 6M6 6l12 12'],
    ];

    $info = $statusMap[$status] ?? $statusMap['DRAFT'];
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $info['bg'] }} {{ $info['text'] }}">
    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $info['icon'] }}" />
    </svg>
    {{ $info['label'] }}
</span>
