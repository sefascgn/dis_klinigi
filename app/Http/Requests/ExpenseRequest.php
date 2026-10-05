<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Requests\Concerns\AuthorizesResource;
use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    use AuthorizesResource;

    protected string $resourceModel = Expense::class;

    protected string $routeParameter = 'expense';

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'spent_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category' => 'kategori',
            'description' => 'açıklama',
            'method' => 'ödeme yöntemi',
            'spent_at' => 'tarih',
        ];
    }
}
