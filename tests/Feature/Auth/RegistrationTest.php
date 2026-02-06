<?php

test('new users can register', function () {
    $response = $this->postJson('/api/v0/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['access_token', 'token_type', 'user']);
});