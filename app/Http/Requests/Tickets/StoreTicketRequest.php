<?php

namespace App\Http\Requests\Tickets;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:20000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'assigned_to' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $assigneeId = $this->input('assigned_to');
            if ($assigneeId && ! User::whereKey($assigneeId)->assignable()->exists()) {
                $validator->errors()->add('assigned_to', 'The selected user cannot be assigned tickets.');
            }
        });
    }
}
