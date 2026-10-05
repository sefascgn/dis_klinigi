<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\PaymentRequest;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();
        $method = PaymentMethod::tryFrom((string) $request->query('method'));

        $query = Payment::query()
            ->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])
            ->when($method, fn ($q) => $q->where('method', $method))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->whereHas('patient', fn ($p) => $p->search($term)));

        $total = (float) (clone $query)->sum('amount');

        $payments = $query
            ->with(['patient', 'receiver'])
            ->latest('paid_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('payments.index', compact('payments', 'from', 'to', 'total'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Payment::class);

        $payment = new Payment([
            'patient_id' => $request->integer('patient_id') ?: null,
            'paid_at' => today(),
            'method' => PaymentMethod::Cash,
        ]);

        return view('payments.create', $this->formData($payment));
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $payment = new Payment($request->validated());
        $payment->received_by = $request->user()->id;
        $payment->save();

        return redirect()->route('patients.show', $payment->patient_id)->with('success', 'Ödeme kaydedildi.');
    }

    public function edit(Payment $payment): View
    {
        Gate::authorize('update', $payment);

        return view('payments.edit', $this->formData($payment));
    }

    public function update(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        $payment->update($request->validated());

        return redirect()->route('patients.show', $payment->patient_id)->with('success', 'Ödeme güncellendi.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        Gate::authorize('delete', $payment);

        $payment->delete();

        return back()->with('success', 'Ödeme kaydı silindi.');
    }

    private function formData(Payment $payment): array
    {
        $patient = $payment->patient_id
            ? Patient::withTrashed()->withBalance()->find($payment->patient_id)
            : null;

        return compact('payment', 'patient');
    }
}
