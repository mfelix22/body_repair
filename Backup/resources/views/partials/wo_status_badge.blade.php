@php
    $statusMap = [
        'on_progress' => ['label' => 'Pending', 'class' => 'warning'],
        'in_progress' => ['label' => 'Working', 'class' => 'primary'],
        'completed' => ['label' => 'Completed', 'class' => 'success'],
        'invoiced' => ['label' => 'Invoiced', 'class' => 'success'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'danger'],
    ];
    $s = $statusMap[$status ?? ''] ?? [
        'label' => ucwords(str_replace('_', ' ', $status ?? '')),
        'class' => 'secondary',
    ];
@endphp
<span class="badge badge-{{ $s['class'] }}">{{ $s['label'] }}</span>
