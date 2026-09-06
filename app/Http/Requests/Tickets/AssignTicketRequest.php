<?php

namespace App\Http\Requests\Tickets;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            // null / empty => unassign
            'assigned_to' => ['present', 'nullable', 'integer'],
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

    public function resolvedAssignee(): ?User
    {
        return $this->input('assigned_to')
            ? User::find($this->input('assigned_to'))
            : null;
    }
}
