<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\AuthorizesResource;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    use AuthorizesResource;

    protected string $resourceModel = User::class;

    protected string $routeParameter = 'user';

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s()-]{7,20}$/'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['boolean'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Yönetici kendi rolünü düşüremez veya kendi hesabını pasifleştiremez.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->user()->is($this->route('user'))) {
                    return;
                }

                if ($this->input('role') !== UserRole::Admin->value) {
                    $validator->errors()->add('role', 'Kendi yönetici yetkinizi kaldıramazsınız.');
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'Kendi hesabınızı pasifleştiremezsiniz.');
                }
            },
        ];
    }
}
