<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            ? Gate::allows('update', $customer)
            : Gate::allows('erp.customers.update');
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:200'],
            'email'              => ['nullable', 'email', 'max:255'],
            'phone'              => ['nullable', 'string', 'max:30'],
            'tax_number'         => ['nullable', 'string', 'max:30'],
            'address'            => ['nullable', 'string', 'max:500'],
            'contact_person'     => ['nullable', 'string', 'max:100'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'credit_limit'       => ['nullable', 'numeric', 'min:0'],
            'status'             => ['nullable', 'in:active,inactive'],
        ];
    }
}
