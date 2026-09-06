<?php

namespace App\Http\Requests\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'required', 'string', 'max:20000'],
            'category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')],
            'priority' => ['sometimes', 'required', Rule::enum(TicketPriority::class)],
            'status' => ['sometimes', 'required', Rule::enum(TicketStatus::class)],
            'resolution' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'assigned_to' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->has('assigned_to')) {
                return;
            }

            $assigneeId = $this->input('assigned_to');
            if ($assigneeId && ! User::whereKey($assigneeId)->assignable()->exists()) {
                $validator->errors()->add('assigned_to', 'The selected user cannot be assigned tickets.');
            }

            // Requiring a resolution note when resolving keeps the log useful.
            if ($this->input('status') === TicketStatus::Resolved->value
                && blank($this->input('resolution'))
                && blank($this->route('ticket')->resolution)) {
                $validator->errors()->add('resolution', 'Add a resolution note before marking the ticket resolved.');
            }
        });
    }
}
