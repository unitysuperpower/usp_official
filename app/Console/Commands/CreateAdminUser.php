<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Create a new admin user');
        $this->newLine();

        $name = $this->ask('Name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');
        $passwordConfirmation = $this->secret('Confirm Password');

        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match!');
            return 1;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->newLine();
        $this->info('Admin user created successfully!');
        $this->table(['ID', 'Name', 'Email'], [
            [$user->id, $user->name, $user->email]
        ]);

        return 0;
    }
}
