<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use DatabaseTransactions;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::whereHas('role', fn ($q) => $q->where('slug', Role::EMPLOYEE))->firstOrFail();
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->user)->get('/profile');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_update_profile_name_and_phone(): void
    {
        $response = $this->actingAs($this->user)->put('/profile', [
            'name'  => 'Budi Santoso Updated',
            'phone' => '+62 812 9999 8888',
        ]);

        $response->assertRedirect();
        $this->user->refresh();
        $this->assertEquals('Budi Santoso Updated', $this->user->name);
        $this->assertEquals('+62 812 9999 8888', $this->user->phone);
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('my-avatar.jpg', 200, 200);

        $response = $this->actingAs($this->user)->post('/profile', [
            '_method' => 'put',
            'name'    => $this->user->name,
            'phone'   => $this->user->phone,
            'avatar'  => $file,
        ]);

        $response->assertRedirect();
        $this->user->refresh();
        $this->assertNotNull($this->user->avatar);
        Storage::disk('public')->assertExists($this->user->avatar);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        // Set known password
        $this->user->password = Hash::make('OldPassword123!');
        $this->user->must_change_password = true;
        $this->user->save();

        $response = $this->actingAs($this->user)->put('/profile/password', [
            'current_password'      => 'OldPassword123!',
            'password'              => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass123!', $this->user->password));
        $this->assertFalse($this->user->must_change_password);
    }

    public function test_password_change_fails_with_incorrect_current_password(): void
    {
        $this->user->password = Hash::make('CorrectPassword123!');
        $this->user->save();

        $response = $this->actingAs($this->user)->put('/profile/password', [
            'current_password'      => 'WrongPassword123!',
            'password'              => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    public function test_user_with_must_change_password_is_redirected_to_profile(): void
    {
        $this->user->must_change_password = true;
        $this->user->save();

        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertRedirect('/profile');
    }
}
