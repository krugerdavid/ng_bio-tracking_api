<?php

use App\Mail\MemberInviteMail;
use App\Models\Member;
use App\Models\MemberInvite;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    config(['app.frontend_url' => 'https://ng-biotracker.netlify.app']);
});

test('invite requires auth', function () {
    $member = Member::factory()->create(['email' => 'a@example.com']);
    $this->postJson('/api/members/'.$member->id.'/invite')->assertStatus(401);
});

test('member role cannot invite', function () {
    $member = Member::factory()->create(['email' => 'a@example.com']);
    Sanctum::actingAs(User::factory()->member()->create());
    $this->postJson('/api/members/'.$member->id.'/invite')->assertStatus(403);
});

test('invite without email fails when member has no email', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->admin()->create());
    $member = Member::factory()->create(['email' => null]);

    $this->postJson('/api/members/'.$member->id.'/invite', [])
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', fn ($m) => str_contains($m, 'obligatorio'));
});

test('admin can invite member and creates user link and sends mail', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->admin()->create());
    $member = Member::factory()->create(['email' => 'athlete@example.com', 'user_id' => null]);

    $response = $this->postJson('/api/members/'.$member->id.'/invite');

    $response->assertStatus(200)
        ->assertJsonPath('data.email', 'athlete@example.com')
        ->assertJsonPath('data.user_id', fn ($id) => $id !== null);

    $this->assertDatabaseHas('users', [
        'email' => 'athlete@example.com',
        'role' => 'member',
    ]);

    $user = User::where('email', 'athlete@example.com')->first();
    expect((int) $member->fresh()->user_id)->toBe($user->id);
    expect(MemberInvite::where('member_id', $member->id)->count())->toBe(1);

    Mail::assertQueued(MemberInviteMail::class, function (MemberInviteMail $mail) use ($member) {
        return $mail->hasTo('athlete@example.com')
            && str_contains($mail->acceptUrl, 'https://ng-biotracker.netlify.app/accept-invite?token=')
            && $mail->member->id === $member->id;
    });
});

test('admin can invite providing email when member has none', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->admin()->create());
    $member = Member::factory()->create(['email' => null]);

    $this->postJson('/api/members/'.$member->id.'/invite', [
        'email' => 'nuevo@example.com',
    ])->assertStatus(200);

    expect($member->fresh()->email)->toBe('nuevo@example.com');
    $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
});

test('invite rejects email already used by another member', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->admin()->create());
    Member::factory()->create(['email' => 'taken@example.com']);
    $member = Member::factory()->create(['email' => null]);

    $this->postJson('/api/members/'.$member->id.'/invite', [
        'email' => 'taken@example.com',
    ])->assertStatus(422);
});

test('accept invite sets password and allows login', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->admin()->create());
    $member = Member::factory()->create(['email' => 'join@example.com']);

    $this->postJson('/api/members/'.$member->id.'/invite')->assertStatus(200);

    $plain = null;
    Mail::assertQueued(MemberInviteMail::class, function (MemberInviteMail $mail) use (&$plain) {
        parse_str(parse_url($mail->acceptUrl, PHP_URL_QUERY), $query);
        $plain = $query['token'] ?? null;

        return true;
    });

    expect($plain)->not->toBeNull();

    $this->postJson('/api/invite/accept', [
        'token' => $plain,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(200);

    $this->postJson('/api/login', [
        'email' => 'join@example.com',
        'password' => 'Password123!',
    ])->assertStatus(200)->assertJsonStructure(['data' => ['access_token']]);

    expect(MemberInvite::where('email', 'join@example.com')->first()->accepted_at)->not->toBeNull();
});

test('accept invite fails with invalid token', function () {
    $this->postJson('/api/invite/accept', [
        'token' => str_repeat('a', 64),
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(422);
});

test('resend invite replaces previous token', function () {
    Mail::fake();
    Sanctum::actingAs(User::factory()->admin()->create());
    $member = Member::factory()->create(['email' => 'resend@example.com']);

    $this->postJson('/api/members/'.$member->id.'/invite')->assertStatus(200);
    $firstToken = MemberInvite::where('member_id', $member->id)->value('token');

    $this->postJson('/api/members/'.$member->id.'/invite')->assertStatus(200)
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'reenviada'));

    expect(MemberInvite::where('member_id', $member->id)->whereNull('accepted_at')->count())->toBe(1);
    expect(MemberInvite::where('member_id', $member->id)->value('token'))->not->toBe($firstToken);
    Mail::assertQueued(MemberInviteMail::class, 2);
});
