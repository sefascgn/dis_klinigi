<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Expense::class);
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();
        $category = ExpenseCategory::tryFrom((string) $request->query('category'));

        $query = Expense::query()
            ->whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('description', 'like', "%{$term}%"));

        $total = (float) (clone $query)->sum('amount');

        // Kategori bazında toplam (seçili aralıkta)
        $byCategory = (clone $query)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $expenses = $query->with('creator')->latest('spent_at')->latest('id')->paginate(20)->withQueryString();

        return view('expenses.index', compact('expenses', 'from', 'to', 'total', 'byCategory'));
    }

    /**
     * ?copy={id} ile mevcut bir gider (örn. aylık kira) bugünün tarihiyle kopyalanır.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Expense::class);

        $source = $request->integer('copy') ? Expense::find($request->integer('copy')) : null;

        $expense = $source
            ? $source->replicate(['created_by'])->fill(['spent_at' => today()])
            : new Expense(['spent_at' => today(), 'method' => PaymentMethod::BankTransfer]);

        return view('expenses.create', compact('expense'));
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $expense = new Expense($request->validated());
        $expense->created_by = $request->user()->id;
        $expense->save();

        return redirect()
            ->route('expenses.index', ['from' => $expense->spent_at->copy()->startOfMonth()->toDateString(), 'to' => $expense->spent_at->toDateString()])
            ->with('success', 'Gider kaydedildi.');
    }

    public function edit(Expense $expense): View
    {
        Gate::authorize('update', $expense);

        return view('expenses.edit', compact('expense'));
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()->route('expenses.index')->with('success', 'Gider güncellendi.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);

        $expense->delete();

        return back()->with('success', 'Gider silindi.');
    }
}
