<?php

use App\Jobs\NotifyApprovedNeverLoggedInJob;
use App\Mail\RegistrationApprovedMail;
use App\Models\Member;
use App\Models\MemberInvite;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

test('approving a pending member queues a login-ready mail', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/register', [
        'name' => 'Aprobar Mail',
        'email' => 'aprobar-mail@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(201);

    $member = Member::where('email', 'aprobar-mail@example.com')->first();

    $this->postJson('/api/members/'.$member->id.'/approve')->assertStatus(200);

    Mail::assertQueued(RegistrationApprovedMail::class, function (RegistrationApprovedMail $mail) use ($member) {
        return $mail->hasTo('aprobar-mail@example.com')
            && $mail->member->is($member)
            && str_contains($mail->loginUrl, '/login');
    });
});

test('notify never logged in command dry-run lists recipients without sending', function () {
    Mail::fake();

    $never = User::factory()->member()->create([
        'status' => 'active',
        'last_login_at' => null,
        'email' => 'nunca@example.com',
    ]);
    Member::factory()->create(['user_id' => $never->id, 'email' => $never->email, 'name' => 'Nunca Entro']);

    $logged = User::factory()->member()->create([
        'status' => 'active',
        'last_login_at' => now(),
        'email' => 'ya@example.com',
    ]);
    Member::factory()->create(['user_id' => $logged->id, 'email' => $logged->email]);

    $this->artisan('members:notify-never-logged-in', ['--dry-run' => true])
        ->expectsTable(
            ['ID', 'Nombre', 'Email'],
            [[$never->id, 'Nunca Entro', 'nunca@example.com']]
        )
        ->assertSuccessful();

    Mail::assertNothingQueued();
});

test('notify never logged in job emails approved members who never logged in', function () {
    Mail::fake();

    $target = User::factory()->member()->create([
        'status' => 'active',
        'last_login_at' => null,
        'email' => 'avisar@example.com',
    ]);
    Member::factory()->create(['user_id' => $target->id, 'email' => $target->email]);

    $pending = User::factory()->member()->create([
        'status' => 'pending',
        'last_login_at' => null,
        'email' => 'pendiente@example.com',
    ]);
    Member::factory()->create(['user_id' => $pending->id, 'email' => $pending->email]);

    $invited = User::factory()->member()->create([
        'status' => 'active',
        'last_login_at' => null,
        'email' => 'invitado@example.com',
    ]);
    $invitedMember = Member::factory()->create(['user_id' => $invited->id, 'email' => $invited->email]);
    MemberInvite::query()->create([
        'member_id' => $invitedMember->id,
        'user_id' => $invited->id,
        'email' => $invited->email,
        'token' => hash('sha256', 'plain'),
        'expires_at' => now()->addDay(),
    ]);

    $sent = (new NotifyApprovedNeverLoggedInJob)->handle();

    expect($sent)->toBe(1);
    Mail::assertQueued(RegistrationApprovedMail::class, 1);
    Mail::assertQueued(RegistrationApprovedMail::class, fn (RegistrationApprovedMail $mail) => $mail->hasTo('avisar@example.com'));
});
