<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartClassSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $classroom = $this->route('classroom');

        return $classroom && ($this->user()?->can('manage', $classroom) ?? false);
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'code_ttl_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
        ];
    }
}
