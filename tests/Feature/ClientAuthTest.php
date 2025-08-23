<?php

use App\Models\Client;
use App\Models\User;

test('client can register', function () {
    $response = $this->postJson('/api/client/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'phone' => '123-456-7890',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'client' => ['id', 'user_id', 'name', 'email', 'phone'],
            'token',
        ]);

    expect(User::where('email', 'john@example.com')->exists())->toBeTrue();
    expect(Client::where('email', 'john@example.com')->exists())->toBeTrue();
});

test('client can login', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => bcrypt('password123'),
    ]);

    Client::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => 'john@example.com',
        'phone' => '123-456-7890',
    ]);

    $response = $this->postJson('/api/client/login', [
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'client' => ['id', 'user_id', 'name', 'email'],
            'token',
        ]);
});

test('client login requires valid credentials', function () {
    $response = $this->postJson('/api/client/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
