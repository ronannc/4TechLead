<?php

namespace App\Http\Requests\Admin;

use App\Models\McpPromptVersion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class SaveMcpPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('create', McpPromptVersion::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:100000'],
        ];
    }
}
