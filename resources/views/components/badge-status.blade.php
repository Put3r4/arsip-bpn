@props([
    'status',
])

@php
use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;

$label = '';
$classes = '';

if ($status instanceof DocumentStatus) {
    $label = $status->label();
    $classes = $status->badgeClasses();
} elseif ($status instanceof LoanStatus) {
    $label = $status->label();
    $classes = $status->badgeClasses();
} else {
    // string fallback
    $str = (string) $status;
    $label = ucfirst($str);
    $classes = match($str) {
        'available', 'returned', 'aktif', '0' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'booked', 'pending', '4' => 'bg-amber-100 text-amber-800 border-amber-300',
        'borrowed', '1' => 'bg-rose-100 text-rose-800 border-rose-300',
        'lost', '2' => 'bg-slate-100 text-slate-800 border-slate-300',
        'invalid', '3', 'cancelled' => 'bg-zinc-100 text-zinc-700 border-zinc-300',
        default => 'bg-gray-100 text-gray-800 border-gray-300',
    };
}
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border shadow-2xs {{ $classes }}">
    {{ $label }}
</span>
