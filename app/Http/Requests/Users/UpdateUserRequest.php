<?php

namespace App\Http\Requests\Users;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:180', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::enum(RoleName::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Admins must not lock themselves out of the admin role.
            if ($this->route('user')->id === $this->user()->id
                && $this->input('role') !== RoleName::Admin->value) {
                $validator->errors()->add('role', 'You cannot change your own role.');
            }
        });
    }
}
