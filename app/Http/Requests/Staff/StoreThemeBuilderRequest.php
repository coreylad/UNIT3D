<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreThemeBuilderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->group->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'base_style' => ['required', 'integer', Rule::in([2, 3, 4, 5, 6, 7, 8, 9])],
            'variables' => ['required', 'string'],
            'body_font' => ['nullable', 'string', 'max:200'],
            'heading_font' => ['nullable', 'string', 'max:200'],
            'extra_css' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
