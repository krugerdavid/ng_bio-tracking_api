<?php

namespace App\Http\Requests\Api;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = Member::find($this->route('member'));
        if (! $member) {
            return true; // controller returns 404
        }

        return $this->user()?->can('invite', $member) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
