<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class BlockTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Tenant $tenant */
        $tenant = $this->route('tenant');

        return $this->user('admin')?->can('block', $tenant) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
