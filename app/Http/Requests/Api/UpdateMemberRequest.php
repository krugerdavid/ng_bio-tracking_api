<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = \App\Models\Member::find($this->route('member'));
        if (! $member) {
            return true; // Let controller return 404
        }
        return $this->user()?->can('update', $member);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('member');
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', Rule::unique('members', 'document_number')->ignore($id)],
            'email' => ['nullable', 'email', Rule::unique('members', 'email')->ignore($id)],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
        ];
    }
}
