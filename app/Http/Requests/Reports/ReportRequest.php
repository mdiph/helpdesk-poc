<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view-reports');
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'format' => ['nullable', 'in:csv,xlsx,pdf'],
            // optional extra filters, reused from the ticket list
            'status' => ['nullable'],
            'priority' => ['nullable'],
            'category_id' => ['nullable'],
            'support_tier' => ['nullable', 'in:l1,l2'],
            'assigned_to' => ['nullable'],
        ];
    }

    public function from(): Carbon
    {
        return $this->date('from') ?: now()->subDays(30)->startOfDay();
    }

    public function to(): Carbon
    {
        return $this->date('to') ?: now()->endOfDay();
    }
}
