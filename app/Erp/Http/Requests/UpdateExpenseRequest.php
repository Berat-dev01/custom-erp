<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('expense');

        return $expense instanceof Expense
            ? Gate::allows('update', $expense)
            : Gate::allows('erp.expenses.update');
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:200'],
            'category'       => ['required', 'in:office,travel,utilities,salary,rent,marketing,other'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'expense_date'   => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,credit_card,other'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'receipt'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
