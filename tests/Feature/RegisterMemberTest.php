<?php

use App\Mail\NewRegistrationAdminMail;
use App\Mail\WelcomeMemberMail;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

test('register creates pending user and member without token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Nuevo Alumno',
        'email' => 'alumno@example.com',
        'training_group' => '0600',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(201);
    $response->assertJsonMissingPath('data.access_token');

    $this->assertDatabaseHas('users', [
        'email' => 'alumno@example.com',
        'role' => 'member',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('members', [
        'email' => 'alumno@example.com',
        'training_group' => '0600',
    ]);

    $user = User::where('email', 'alumno@example.com')->first();
    $member = Member::where('email', 'alumno@example.com')->first();
    expect((int) $member->user_id)->toBe($user->id);
});

test('register sends a welcome mail to the member and a notification to each admin/root', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create(['email' => 'nico@example.com']);
    $root = User::factory()->root()->create(['email' => 'root@example.com']);
    User::factory()->member()->create(['email' => 'otro-alumno@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Nuevo Alumno',
        'email' => 'alumno-mail@example.com',
        'training_group' => '06:00',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(201);

    Mail::assertQueued(WelcomeMemberMail::class, function (WelcomeMemberMail $mail) {
        return $mail->hasTo('alumno-mail@example.com') && $mail->member->name === 'Nuevo Alumno';
    });

    Mail::assertQueued(NewRegistrationAdminMail::class, 2);
    Mail::assertQueued(NewRegistrationAdminMail::class, fn (NewRegistrationAdminMail $mail) => $mail->hasTo($admin->email));
    Mail::assertQueued(NewRegistrationAdminMail::class, fn (NewRegistrationAdminMail $mail) => $mail->hasTo($root->email));
});

test('register rejects duplicate email already used by a user', function () {
    User::factory()->create(['email' => 'existe@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Otro',
        'email' => 'existe@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(422);
});

test('register rejects duplicate email already used by a member', function () {
    Member::factory()->create(['email' => 'miembro@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Otro',
        'email' => 'miembro@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(422);
});

test('login is blocked while registration is pending', function () {
    $this->postJson('/api/register', [
        'name' => 'Pendiente',
        'email' => 'pendiente@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(201);

    $response = $this->postJson('/api/login', [
        'email' => 'pendiente@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.email.0', fn ($m) => str_contains($m, 'pendiente de aprobación'));
});

test('admin can approve a pending member and login succeeds afterwards', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/register', [
        'name' => 'Aprobar',
        'email' => 'aprobar@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(201);

    $member = Member::where('email', 'aprobar@example.com')->first();

    $this->postJson('/api/members/'.$member->id.'/approve')
        ->assertStatus(200)
        ->assertJsonPath('data.user_status', 'active');

    $this->assertDatabaseHas('users', [
        'email' => 'aprobar@example.com',
        'status' => 'active',
    ]);

    $this->postJson('/api/login', [
        'email' => 'aprobar@example.com',
        'password' => 'Password123!',
    ])->assertStatus(200)->assertJsonStructure(['data' => ['access_token']]);
});

test('approving an already active member fails', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $member = Member::factory()->create(['email' => 'activo@example.com']);
    $user = User::factory()->member()->create(['email' => 'activo@example.com', 'status' => 'active']);
    $member->update(['user_id' => $user->id]);

    $this->postJson('/api/members/'.$member->id.'/approve')->assertStatus(422);
});

test('member role cannot approve registrations', function () {
    Sanctum::actingAs(User::factory()->member()->create());
    $member = Member::factory()->create();

    $this->postJson('/api/members/'.$member->id.'/approve')->assertStatus(403);
});
