<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'guest_name' => ['required', 'string', 'min:2', 'max:100'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_website' => ['nullable', 'url', 'max:255'],
            'content' => ['required', 'string', 'min:3', 'max:2000'],
            'turnstile_token' => ['required', 'string', 'min:1'],
            'hp_field' => ['nullable', 'string', 'max:0'],
        ];
    }
}
