<?php

namespace App\Http\Requests\Tickets;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'resolution' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('status') === TicketStatus::Resolved->value
                && blank($this->input('resolution'))
                && blank($this->route('ticket')->resolution)) {
                $validator->errors()->add('resolution', 'Add a resolution note before marking the ticket resolved.');
            }
        });
    }
}
