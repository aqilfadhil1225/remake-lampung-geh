<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ListArticlesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'category' => ['nullable', 'string'],
            'tag' => ['nullable', 'string'],
            'author' => ['nullable', 'string'],
            'year' => ['sometimes', 'required', 'integer', 'min:2020'],
            'month' => ['sometimes', 'required', 'integer', 'min:1', 'max:12'],
            'region' => ['sometimes', 'required', 'in:bandar_lampung,lampung,nasional'],
            'has_video' => ['sometimes', 'required', 'boolean'],
            'type' => ['sometimes', 'required', 'in:standard,live_report'],
            'live_status' => ['sometimes', 'required', 'in:live,ended'],
            'q' => ['sometimes', 'required', 'string', 'min:2'],
            'page' => ['sometimes', 'required', 'regex:/^[1-9]\d*$/'],
            'per_page' => ['sometimes', 'required', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Konversi `has_video` string ('true'/'false'/'1'/'0') menjadi boolean
     * agar lolos aturan `boolean` Laravel; string invalid (mis. 'abc')
     * tetap menghasilkan 422 karena filter_var mengembalikan null.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('has_video') && is_string($this->input('has_video'))) {
            $boolean = filter_var($this->input('has_video'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($boolean === null) {
                // Biarkan nilai asli agar gagal validasi `boolean`.
                return;
            }

            $this->merge(['has_video' => $boolean]);
        }
    }
}
