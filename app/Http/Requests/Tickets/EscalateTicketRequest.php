<?php

namespace App\Http\Requests\Tickets;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EscalateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('escalate', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:2000'],
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

            // An escalation target, if given, must be an L2 agent (or admin).
            if ($assigneeId) {
                $ok = User::whereKey($assigneeId)
                    ->whereHas('role', fn ($q) => $q->whereIn('name', [RoleName::L2->value, RoleName::Admin->value]))
                    ->active()
                    ->exists();

                if (! $ok) {
                    $validator->errors()->add('assigned_to', 'Escalated tickets can only be assigned to an L2 agent.');
                }
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
