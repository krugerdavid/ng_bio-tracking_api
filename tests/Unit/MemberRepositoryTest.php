<?php

use App\Models\Member;
use App\Models\User;
use App\Repositories\MemberRepository;

beforeEach(function () {
    $this->repository = app(MemberRepository::class);
});

test('searchForUser returns all members for admin', function () {
    $admin = User::factory()->admin()->create();
    Member::factory()->count(3)->create();

    $result = $this->repository->searchForUser($admin, null, 15);

    expect($result->total())->toBe(3)
        ->and($result->count())->toBe(3);
});

test('searchForUser returns all members for root', function () {
    $root = User::factory()->root()->create();
    Member::factory()->count(2)->create();

    $result = $this->repository->searchForUser($root, null, 15);

    expect($result->total())->toBe(2);
});

test('searchForUser filters by search query for admin', function () {
    $admin = User::factory()->admin()->create();
    Member::factory()->create(['name' => 'John Doe', 'document_number' => '111']);
    Member::factory()->create(['name' => 'Jane Smith', 'document_number' => '222']);

    $result = $this->repository->searchForUser($admin, 'John', 15);

    expect($result->total())->toBe(1)
        ->and($result->first()->name)->toBe('John Doe');
});

test('searchForUser returns only linked member for member role', function () {
    $user = User::factory()->member()->create();
    $linkedMember = Member::factory()->create();
    $linkedMember->update(['user_id' => $user->id]);
    Member::factory()->count(2)->create(); // others

    $result = $this->repository->searchForUser($user, null, 15);

    expect($result->total())->toBe(1)
        ->and($result->first()->id)->toBe($linkedMember->id);
});

test('searchForUser returns empty for member role with no linked member', function () {
    $user = User::factory()->member()->create();
    Member::factory()->count(2)->create();

    $result = $this->repository->searchForUser($user, null, 15);

    expect($result->total())->toBe(0);
});

test('search filters by query', function () {
    Member::factory()->create(['name' => 'Alice', 'document_number' => '111']);
    Member::factory()->create(['name' => 'Bob', 'document_number' => '222']);

    $result = $this->repository->search('Alice', 15);

    expect($result->total())->toBe(1)
        ->and($result->first()->name)->toBe('Alice');
});
