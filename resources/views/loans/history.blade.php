<x-app-layout title="Riwayat Jejak Audit Berkas">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[var(--color-primary)]">
                    Riwayat Jejak Audit Berkas: {{ $document->document_number }}
                </h1>
                <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
                    Daftar seluruh transaksi peminjaman dan pengembalian fisik warkah pertanahan secara kronologis
                </p>
            </div>
            <div>
                <a 
                    href="/documents" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] hover:bg-[var(--color-surface)] transition"
                >
                    &larr; Kembali ke Katalog
                </a>
            </div>
        </div>
    </x-slot>

    <div class="col-span-12 space-y-4">
        <x-card title="Informasi Dokumen Arsip" subtitle="Rincian berkas yang diaudit">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-slate-500 block">Nomor Berkas:</span>
                    <strong class="text-sm text-[var(--color-primary)]">{{ $document->document_number }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Tahun:</span>
                    <span class="font-mono">{{ $document->year ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Rak Penyimpanan:</span>
                    <span class="font-semibold">{{ $document->storageRack?->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Status Fisik:</span>
                    <x-badge-status :status="$document->status->value" />
                </div>
            </div>
        </x-card>

        <x-card title="Catatan Kronologis Peminjaman" subtitle="Histori lengkap dari transaksi terbaru hingga terdahulu">
            @if($loans->isEmpty())
                <div class="py-8 text-center text-xs text-slate-500">
                    Belum ada riwayat transaksi peminjaman untuk dokumen ini.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[var(--color-surface)] text-[var(--color-text-muted)] font-semibold border-b border-[var(--color-surface-border)] uppercase text-[10px]">
                            <tr>
                                <th class="px-3 py-2.5">Peminjam & Seksi</th>
                                <th class="px-3 py-2.5">Kegiatan Pelayanan</th>
                                <th class="px-3 py-2.5">Tgl Pinjam</th>
                                <th class="px-3 py-2.5">Tgl Kembali</th>
                                <th class="px-3 py-2.5 text-center">Status</th>
                                <th class="px-3 py-2.5">Disetujui Oleh</th>
                                <th class="px-3 py-2.5">Diterima Oleh</th>
                                <th class="px-3 py-2.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-surface-border)]">
                            @foreach($loans as $loan)
                                <tr class="hover:bg-[var(--color-surface)]/40 transition">
                                    <td class="px-3 py-2.5 font-medium text-[var(--color-text-main)]">
                                        <div>{{ $loan->borrower?->name ?? '-' }}</div>
                                        <div class="text-[10px] text-slate-500">{{ $loan->borrower?->section?->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-[var(--color-text-main)]">{{ $loan->serviceActivity?->name ?? '-' }}</td>
                                    <td class="px-3 py-2.5 font-mono text-slate-600">{{ $loan->loan_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-3 py-2.5 font-mono text-slate-600">{{ $loan->return_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $loan->status->badgeClasses() }}">
                                            {{ $loan->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-slate-600 text-[11px]">{{ $loan->approver?->name ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-slate-600 text-[11px]">{{ $loan->receiver?->name ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-slate-500 text-[11px] italic">{{ $loan->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
