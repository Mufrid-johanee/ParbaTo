<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Classroom::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'room_label' => ['nullable', 'string', 'max:80'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'rows' => ['nullable', 'integer', 'min:1', 'max:20'],
            'cols' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
