<?php

namespace App\Actions\Account;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateUserPasswordAction
{
    /**
     * I-update ang password ng user.
     *
     * @param  \App\Models\User  $user
     * @param  array  $input
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update($user, array $input)
    {
        $validator = Validator::make($input, [
            'currentPassword' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (! Hash::check($input['currentPassword'], $user->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => __('The provided password does not match your current password.'),
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
