<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('join', \App\Models\Classroom::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:16'],
        ];
    }
}
