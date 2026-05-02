<?php

use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('unauthenticated users cannot view options list', function () {
    $this->get(route('options.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view options list', function () {
    $user = User::factory()->create();
    Option::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('options.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('options/Index')
            ->has('options', 3)
        );
});

test('unauthenticated users cannot view the edit option page', function () {
    $option = Option::factory()->create();

    $this->get(route('options.edit', $option))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the edit option page', function () {
    $user = User::factory()->create();
    $option = Option::factory()->create();

    $this->actingAs($user)
        ->get(route('options.edit', $option))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('options/Edit')
            ->has('option')
            ->where('option.id', $option->id)
            ->where('option.key', $option->key)
        );
});

test('edit page returns 404 for non-existent option', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('options.edit', 99999))
        ->assertNotFound();
});

test('unauthenticated users cannot update an option', function () {
    $option = Option::factory()->create(['value' => 'old-value']);

    $this->patch(route('options.update', $option), ['value' => 'new-value'])
        ->assertRedirect(route('login'));
});

test('authenticated users can update an option value', function () {
    $user = User::factory()->create();
    $option = Option::factory()->create(['value' => 'old-value']);

    $this->actingAs($user)
        ->patch(route('options.update', $option), ['value' => 'new-value'])
        ->assertRedirect(route('options.index'));

    expect(Option::find($option->id)->value)->toBe('new-value');
});

test('authenticated users can clear an option value', function () {
    $user = User::factory()->create();
    $option = Option::factory()->create(['value' => 'some-value']);

    $this->actingAs($user)
        ->patch(route('options.update', $option), ['value' => null])
        ->assertRedirect(route('options.index'));

    expect(Option::find($option->id)->value)->toBeNull();
});

test('update validates that value is a string', function () {
    $user = User::factory()->create();
    $option = Option::factory()->create();

    $this->actingAs($user)
        ->patch(route('options.update', $option), ['value' => str_repeat('x', 10001)])
        ->assertSessionHasErrors('value');
});

test('update returns 404 for non-existent option', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('options.update', 99999), ['value' => 'test'])
        ->assertNotFound();
});
