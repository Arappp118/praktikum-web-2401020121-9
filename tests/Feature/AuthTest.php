<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => 'Mahasiswa Praktikum',
            'email' => 'mahasiswa@example.test',
            'password' => Hash::make('LatihanWeb123!'),
        ]);
    }

    public function test_guest_cannot_access_buku_and_is_redirected_to_login(): void
    {
        $response = $this->get('/buku');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Login Praktikum');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'mahasiswa@example.test',
            'password' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        $this->assertGuest();
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::where('email', 'mahasiswa@example.test')->first();

        $response = $this->post('/login', [
            'email' => 'mahasiswa@example.test',
            'password' => 'LatihanWeb123!',
        ]);

        $response->assertRedirect(route('buku.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_access_buku_and_sees_name(): void
    {
        $user = User::where('email', 'mahasiswa@example.test')->first();

        $response = $this->actingAs($user)->get('/buku');
        $response->assertStatus(200);
        $response->assertSee('Daftar Buku');
        $response->assertSee('Masuk sebagai ' . $user->name);
        $response->assertSee('Keluar');
    }

    public function test_user_can_logout_and_cannot_access_buku_anymore(): void
    {
        $user = User::where('email', 'mahasiswa@example.test')->first();

        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();

        $responseAfterLogout = $this->get('/buku');
        $responseAfterLogout->assertRedirect('/login');
    }
}
