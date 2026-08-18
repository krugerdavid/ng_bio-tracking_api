<?php

namespace App\Actions;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction implements Action
{
    /**
     * @param string $email
     * @param string $password
     * @return array
     * @throws ValidationException
     */
    public function execute(...$args): array
    {
        [$email, $password] = $args;

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if ($user->status === UserStatus::Pending) {
            throw ValidationException::withMessages([
                'email' => ['Tu registro está pendiente de aprobación. El profe te va a avisar apenas lo confirme.'],
            ]);
        }

        if ($user->status === UserStatus::Rejected) {
            throw ValidationException::withMessages([
                'email' => ['Tu registro no fue aprobado. Contactá al profe para más información.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
