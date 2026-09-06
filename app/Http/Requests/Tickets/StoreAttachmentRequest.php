<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAttachments', $this->route('ticket'));
    }

    public function rules(): array
    {
        $maxKb = (int) config('helpdesk.attachments.max_size_kb', 10240);
        $extensions = config('helpdesk.attachments.allowed_extensions', []);

        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'file',
                "max:{$maxKb}",
                Rule::when(! empty($extensions), ['mimes:'.implode(',', $extensions)]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.*.mimes' => 'That file type is not allowed.',
            'files.*.max' => 'Each file must be smaller than :max kB.',
        ];
    }
}
