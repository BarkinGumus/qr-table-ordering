<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('platform:create-admin {email} {--name=Platform Admin}')]
#[Description('Create a platform admin account (the first login on a new server)')]
class CreatePlatformAdmin extends Command
{
    public function handle(): int
    {
        $password = $this->secret('Password (at least 12 characters)');

        $validator = Validator::make(
            ['email' => $this->argument('email'), 'password' => $password],
            [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', Password::min(12)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'restaurant_id' => null,
            'name' => $this->option('name'),
            'email' => $this->argument('email'),
            'password' => $password,
            'role' => Role::PlatformAdmin,
        ]);

        $this->info('Platform admin created. Log in at /admin/login.');

        return self::SUCCESS;
    }
}
