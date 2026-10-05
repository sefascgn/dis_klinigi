{{-- Parametreler: $payments, $showPatient (bool) --}}
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Tarih</th>
                @if ($showPatient)<th>Hasta</th>@endif
                <th>Yöntem</th>
                <th class="hidden md:table-cell">Not</th>
                <th class="hidden lg:table-cell">Alan</th>
                <th class="text-right">Tutar</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td class="whitespace-nowrap">{{ $payment->paid_at->format('d.m.Y') }}</td>
                    @if ($showPatient)
                        <td><a href="{{ route('patients.show', $payment->patient_id) }}" class="link">{{ $payment->patient->full_name }}</a></td>
                    @endif
                    <td>{{ $payment->method->label() }}</td>
                    <td class="hidden max-w-xs truncate md:table-cell">{{ $payment->notes ?? '—' }}</td>
                    <td class="hidden lg:table-cell">{{ $payment->receiver?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap text-right font-medium text-emerald-700">@money($payment->amount)</td>
                    <td class="whitespace-nowrap text-right">
                        <a href="{{ route('print.receipt', $payment) }}" target="_blank" class="btn btn-sm text-gray-600 hover:bg-gray-100 focus:ring-primary-500" title="Makbuz yazdır">
                            <x-icon name="printer" class="h-4 w-4" />
                            <span class="sr-only sm:not-sr-only">Makbuz</span>
                        </a>
                        @can('update', $payment)
                            <x-edit-link :href="route('payments.edit', $payment)" />
                        @endcan
                        @can('delete', $payment)
                            <x-delete-button :action="route('payments.destroy', $payment)" confirm="Ödeme kaydı silinsin mi?" />
                        @endcan
                    </td>
                </tr>
            @empty
                <x-empty-row :colspan="6 + (int) $showPatient" message="Ödeme kaydı yok." />
            @endforelse
        </tbody>
    </table>
</div>
