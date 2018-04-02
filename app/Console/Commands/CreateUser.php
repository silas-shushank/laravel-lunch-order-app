<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;


class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $email = $this->ask('Enter email address');
        $password = $this->secret('Enter password (min:6)');

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
        ],
            [
                'email' => 'bail|required|string|email|unique:users',
                'password' => 'required|string|min:6',
            ]
        );

        if ($validator->fails()) {
            $this->alert('Validation failed!');
            $this->error($validator->messages()->first());
            return 1;
        }

        // Fetch all roles from database
        $roles = Role::all()->pluck('name')->toArray();

        $roleName = $this->choice('Choose a role', $roles);

        try {
            $this->line('Creating new user ...');
            $user = User::create([
                'email' => $email,
                'password' => bcrypt($password),
            ]);

            $this->line('Assigning role ...');
            $user->assignRole($roleName);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            $this->alert('Error saving user!');
            $this->error($e->getMessage());
            return 1;
        }

        DB::commit();
        $this->info("User `{$user->email}` with role `{$roleName}` was created.");
        return 0;

    }
}