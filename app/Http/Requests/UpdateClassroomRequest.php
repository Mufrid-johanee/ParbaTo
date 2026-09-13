<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $classroom = $this->route('classroom');

        return $classroom && ($this->user()?->can('update', $classroom) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'room_label' => ['nullable', 'string', 'max:80'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
