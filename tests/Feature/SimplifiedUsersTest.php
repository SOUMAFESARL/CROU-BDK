<?php

namespace Tests\Feature;

use App\Http\Requests\UserRequest;
use App\Http\Services\UserService;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SimplifiedUsersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $configRepo = \Mockery::mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class);
        $configRepo->shouldReceive('getConfig')->andReturn(null);
        $this->app->instance(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class, $configRepo);
        Schema::create('genre', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('users', function (Blueprint $t) {
            $t->string('id_user')->primary();
            $t->string('name'); $t->string('matricule')->nullable(); $t->integer('genre_id'); $t->integer('role_id');
            $t->string('email')->unique(); $t->string('telephone_principal'); $t->string('password');
            $t->string('adresse')->nullable(); $t->string('nationalite')->default('Ivoirienne');
            $t->string('campus_id')->nullable(); $t->string('created_by')->nullable(); $t->string('updated_by')->nullable();
            $t->string('remember_token')->nullable(); $t->string('statut')->nullable(); $t->dateTime('date_inscription')->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        DB::table('genre')->insert(['id' => 1, 'name' => 'Monsieur']);
        DB::table('roles')->insert(['id' => 1, 'name' => 'Administrateur']);
        $this->actingAs(new User(['id_user' => 'actor', 'role_id' => 1, 'campus_id' => 'campus']), 'admin');
    }

    public function test_profile_password_change_only_updates_authenticated_user_without_old_password(): void
    {
        $user = (new UserService(new UserRepository))->createUser($this->data());
        $this->actingAs($user, 'admin');
        $request = \Illuminate\Http\Request::create('/admin/mon-profil/mot-de-passe', 'PUT', [
            'password'=>'UpdatedPass123!', 'password_confirmation'=>'UpdatedPass123!',
            'id_user'=>'actor', 'role_id'=>99,
        ]);
        $request->setUserResolver(fn() => $user);
        $request->setLaravelSession(app('session.store'));
        $response = app(\App\Http\Controllers\admin\users\ProfileController::class)->updatePassword($request);
        $this->assertSame(route('admin.profile.show'), $response->getTargetUrl());
        $this->assertTrue(Hash::check('UpdatedPass123!', $user->fresh()->password));
        $this->assertSame($user->role_id, $user->fresh()->role_id);
        $this->assertNotEmpty($user->fresh()->remember_token);
    }

    public function test_profile_password_change_rejects_mismatched_confirmation(): void
    {
        $user = (new UserService(new UserRepository))->createUser($this->data());
        $hash = $user->password;
        $request = \Illuminate\Http\Request::create('/admin/mon-profil/mot-de-passe', 'PUT', [
            'password'=>'UpdatedPass123!', 'password_confirmation'=>'OtherPass123!',
        ]);
        $request->setUserResolver(fn() => $user);
        try {
            app(\App\Http\Controllers\admin\users\ProfileController::class)->updatePassword($request);
            $this->fail('Mismatched confirmation accepted');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('password', $e->errors());
            $this->assertSame($hash, $user->fresh()->password);
        }
    }
    public function test_student_profile_password_change_hashes_password_and_rejects_bad_confirmation(): void
    {
        $student = \Mockery::mock(\App\Models\Etudiant::class)->makePartial();
        $student->shouldReceive('save')->once()->andReturn(true);
        $request = \Illuminate\Http\Request::create('/profil/mot-de-passe', 'PUT', [
            'password'=>'UpdatedPass123!', 'password_confirmation'=>'UpdatedPass123!',
            'id_etudiant'=>'someone-else',
        ]);
        $request->setUserResolver(fn($guard) => $guard === 'etudiant' ? $student : null);
        $request->setLaravelSession(app('session.store'));
        $controller = app(\App\Http\Controllers\etudiant\dashboardController::class);
        $response = $controller->updatePassword($request);
        $this->assertTrue(Hash::check('UpdatedPass123!', $student->password));
        $this->assertNotEmpty($student->remember_token);
        $this->assertStringContainsString('/dashboard', $response->getTargetUrl());
        $request->merge(['password_confirmation'=>'Different123!']);
        try {
            $controller->updatePassword($request);
            $this->fail('Invalid confirmation accepted');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame('passwordChange', $e->errorBag);
            $this->assertArrayHasKey('password', $e->errors());
        }
    }
    public function test_index_is_paginated_by_ten_and_filterable(): void
    {
        $service = new UserService(new UserRepository);
        foreach (range(1, 12) as $i) {
            $service->createUser(array_merge($this->data(), [
                'name' => 'Agent '.$i,
                'email' => "agent{$i}@crou.ci",
            ]));
        }
        $target = User::where('email', 'agent3@crou.ci')->first();
        $target->update(['statut' => 'inactive']);

        $page = $this->usersIndexPage([]);
        $this->assertSame('Admin/Users/Index', $page['component']);
        $this->assertCount(10, $page['props']['users']['data']);
        $this->assertSame(12, $page['props']['users']['total']);
        $this->assertSame(2, $page['props']['users']['last_page']);
        $this->assertArrayNotHasKey('password', $page['props']['users']['data'][0]);

        $this->assertCount(2, $this->usersIndexPage(['page' => 2])['props']['users']['data']);

        $inactive = $this->usersIndexPage(['statut' => 'inactive'])['props']['users'];
        $this->assertSame(1, $inactive['total']);
        $this->assertSame($target->id_user, $inactive['data'][0]['id']);

        $this->assertSame(1, $this->usersIndexPage(['search' => 'agent12@'])['props']['users']['total']);
    }

    private function usersIndexPage(array $query): array
    {
        $request = \Illuminate\Http\Request::create('/admin/utilisateur/liste', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return app(\App\Http\Controllers\admin\users\usersController::class)
            ->index($request, app(\App\Services\AdminAccess::class))
            ->toResponse($request)
            ->getData(true);
    }

    public function test_user_can_be_deactivated_and_reactivated(): void
    {
        $user = (new UserService(new UserRepository))->createUser($this->data());
        $this->withoutMiddleware();
        $this->put(route('admin.users.status', $user->id_user), ['statut' => 'inactive'])->assertRedirect();
        $this->assertSame('inactive', $user->fresh()->statut);
        $this->assertFalse(\Illuminate\Support\Facades\Auth::guard('admin')->attempt([
            'email' => $user->email, 'password' => $this->data()['password'], 'statut' => 'active',
        ]));
        $this->actingAs(new User(['id_user' => 'actor', 'role_id' => 1]), 'admin');
        $this->put(route('admin.users.status', $user->id_user), ['statut' => 'active'])->assertRedirect();
        $this->assertSame('active', $user->fresh()->statut);
    }

    public function test_self_deactivation_is_rejected(): void
    {
        $user = (new UserService(new UserRepository))->createUser($this->data());
        $this->actingAs($user, 'admin')->withoutMiddleware();
        $this->put(route('admin.users.status', $user->id_user), ['statut' => 'inactive'])
            ->assertSessionHas('error');
        $this->assertSame('active', $user->fresh()->statut);
    }
    public function test_login_accepts_legacy_pending_account_but_rejects_deactivated_account(): void
    {
        $user = (new UserService(new UserRepository))->createUser($this->data());
        $user->update(['statut' => 'en_attente']);
        \Illuminate\Support\Facades\Auth::guard('admin')->logout();
        $this->withoutMiddleware();
        $credentials = ['email' => $user->email, 'password' => $this->data()['password']];
        $request = \Illuminate\Http\Request::create('/admin/login', 'POST', $credentials);
        $request->setLaravelSession(app('session.store'));
        $response = (new \App\Http\Controllers\admin\auth\AuthController)->login($request);
        $this->assertSame(route('admin.dashboard.index'), $response->getTargetUrl());
        $this->assertAuthenticatedAs($user, 'admin');
        \Illuminate\Support\Facades\Auth::guard('admin')->logout();
        $user->update(['statut' => 'inactive']);
        (new \App\Http\Controllers\admin\auth\AuthController)->login($request);
        $this->assertTrue(app('session.store')->get('errors')->any());
        $this->assertGuest('admin');
    }
    private function data(): array
    {
        return ['genre_id' => 1, 'name' => 'Utilisateur', 'role_id' => 1,
            'email' => 'utilisateur@example.com', 'telephone_principal' => '0700000000', 'password' => 'SecretFort123!'];
    }

    public function test_creation_accepts_only_the_six_fields_and_hashes_password(): void
    {
        $request = UserRequest::create('/admin/users', 'POST', $this->data());
        $validator = Validator::make($request->all(), $request->rules());
        $this->assertTrue($validator->passes(), implode(' ', $validator->errors()->all()));
        $user = (new UserService(new UserRepository))->createUser($validator->validated());
        $this->assertTrue(Hash::check($this->data()['password'], $user->password));
        $this->assertSame('utilisateur@example.com', $user->email);
        $this->assertSame('Ivoirienne', $user->fresh()->nationalite);
    }

    public function test_phone_requires_exactly_ten_digits_on_creation_and_update(): void
    {
        foreach (['POST', 'PUT'] as $method) {
            $request = UserRequest::create('/admin/users', $method);
            $rules = ['telephone_principal' => $request->rules()['telephone_principal']];
            foreach (['070000000', '07000000000', '07000abcde', '+2250700000000', '07 0000000', "0700000000\n"] as $phone) {
                $validator = Validator::make(['telephone_principal' => $phone], $rules, $request->messages());
                $this->assertTrue($validator->fails(), $method.' accepted '.$phone);
                $this->assertSame('Le téléphone doit contenir exactement 10 chiffres.', $validator->errors()->first('telephone_principal'));
            }
            $this->assertTrue(Validator::make(['telephone_principal' => '0700000000'], $rules)->passes());
        }
    }

    public function test_phone_must_be_ivorian_and_spaces_are_stripped(): void
    {
        $rules = ['telephone_principal' => (new UserRequest)->rules()['telephone_principal']];
        foreach (['0900000000', '1000000000', '3300000000'] as $phone) {
            $validator = Validator::make(['telephone_principal' => $phone], $rules);
            $this->assertTrue($validator->fails(), 'accepted '.$phone);
            $this->assertStringContainsString('numéro ivoirien', $validator->errors()->first('telephone_principal'));
        }
        foreach (['0100000000', '0500000000', '2700000000'] as $phone) {
            $this->assertTrue(Validator::make(['telephone_principal' => $phone], $rules)->passes(), 'rejected '.$phone);
        }

        // Le formulaire envoie « 07 00 00 00 00 » : prepareForValidation retire les espaces.
        $request = UserRequest::create('/admin/users', 'POST', ['telephone_principal' => '07 00 00 00 00']);
        (fn () => $this->prepareForValidation())->call($request);
        $this->assertSame('0700000000', $request->input('telephone_principal'));
    }

    public function test_update_preserves_password_and_removed_fields_when_not_supplied(): void
    {
        $service = new UserService(new UserRepository);
        $user = $service->createUser($this->data());
        $user->update(['adresse' => 'Ancienne adresse', 'nationalite' => 'Autre nationalité']);
        $data = $this->data();
        $data['name'] = 'Nom modifié'; $data['password'] = null;
        $request = UserRequest::create('/admin/users/'.$user->id_user, 'PUT', $data);
        $route = new Route('PUT', 'admin/users/{id_user}', fn () => null);
        $route->bind($request); $route->setParameter('id_user', $user->id_user);
        $request->setRouteResolver(fn () => $route);
        $validator = Validator::make($request->all(), $request->rules());
        $this->assertTrue($validator->passes(), implode(' ', $validator->errors()->all()));
        // Excluded inputs cannot overwrite historical profile data.
        $updated = $service->updateUser($user->id_user, $validator->validated() + ['adresse' => 'Remplacement']);
        $this->assertSame('Nom modifié', $updated->name);
        $this->assertSame('Ancienne adresse', $updated->adresse);
        $this->assertSame('Autre nationalité', $updated->nationalite);
        $this->assertSame($user->password, $updated->password);
    }
}
