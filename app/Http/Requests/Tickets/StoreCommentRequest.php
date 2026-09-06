<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('comment', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:20000'],
            'is_internal' => ['boolean'],
        ];
    }
}
