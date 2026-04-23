<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Jobs\ProvisionWorkspaceOmadaSiteJob;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'brand_name' => ['required', 'string', 'max:100'],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $workspace = Workspace::create([
            'user_id' => $user->id,
            'brand_name' => $input['brand_name'],
            'public_slug' => Workspace::uniquePublicSlugFromBrand($input['brand_name']),
            'provisioning_status' => 'pending',
        ]);

        ProvisionWorkspaceOmadaSiteJob::dispatch($workspace);

        Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']);
        $user->assignRole('guest');

        return $user;
    }
}
