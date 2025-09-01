<?php

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateUserProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    private UpdateUserProfileInformation $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new UpdateUserProfileInformation;
    }

    public function test_can_update_name_and_email(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->action->update($user, [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $user->refresh();

        expect($user->name)->toBe('New Name');
        expect($user->email)->toBe('new@example.com');
        expect($user->email_verified_at)->toBeNull(); // Should be reset due to email change
    }

    public function test_can_update_name_without_changing_email(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Old Name',
            'email' => 'same@example.com',
            'email_verified_at' => now(),
        ]);

        $originalVerifiedAt = $user->email_verified_at;

        $this->action->update($user, [
            'name' => 'New Name',
            'email' => 'same@example.com',
        ]);

        $user->refresh();

        expect($user->name)->toBe('New Name');
        expect($user->email)->toBe('same@example.com');
        expect($user->email_verified_at->toDateTimeString())->toBe($originalVerifiedAt->toDateTimeString());
    }

    public function test_validates_required_name(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->expectException(ValidationException::class);

        $this->action->update($user, [
            'name' => '',
            'email' => 'test@example.com',
        ]);
    }

    public function test_validates_name_max_length(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->expectException(ValidationException::class);

        $this->action->update($user, [
            'name' => str_repeat('a', 256),
            'email' => 'test@example.com',
        ]);
    }

    public function test_validates_required_email(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->expectException(ValidationException::class);

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => '',
        ]);
    }

    public function test_validates_email_format(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->expectException(ValidationException::class);

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => 'invalid-email',
        ]);
    }

    public function test_validates_email_max_length(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->expectException(ValidationException::class);

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => str_repeat('a', 250).'@example.com',
        ]);
    }

    public function test_validates_unique_email(): void
    {
        $existingUser = User::factory()->withPersonalTeam()->create(['email' => 'existing@example.com']);
        $user = User::factory()->withPersonalTeam()->create(['email' => 'user@example.com']);

        $this->expectException(ValidationException::class);

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => 'existing@example.com',
        ]);
    }

    public function test_allows_same_user_to_keep_their_email(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Test Name',
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);

        $originalVerifiedAt = $user->email_verified_at;

        $this->action->update($user, [
            'name' => 'Updated Name',
            'email' => 'user@example.com',
        ]);

        $user->refresh();

        expect($user->name)->toBe('Updated Name');
        expect($user->email)->toBe('user@example.com');
        expect($user->email_verified_at->toDateTimeString())->toBe($originalVerifiedAt->toDateTimeString());
    }

    public function test_can_update_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->withPersonalTeam()->create();
        $photo = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'photo' => $photo,
        ]);

        $user->refresh();

        expect($user->profile_photo_path)->not->toBeNull();
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_validates_photo_file_type(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        $this->expectException(ValidationException::class);

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'photo' => $invalidFile,
        ]);
    }

    public function test_validates_photo_file_size(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $largeFile = UploadedFile::fake()->create('large.jpg', 2048, 'image/jpeg'); // 2MB

        $this->expectException(ValidationException::class);

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'photo' => $largeFile,
        ]);
    }

    public function test_can_update_without_photo(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->action->update($user, [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $user->refresh();

        expect($user->name)->toBe('New Name');
        expect($user->email)->toBe('new@example.com');
        expect($user->profile_photo_path)->toBeNull();
    }

    public function test_resets_email_verification_when_email_changes(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Test Name',
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        expect($user->hasVerifiedEmail())->toBeTrue();

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => 'new@example.com',
        ]);

        $user->refresh();

        expect($user->email)->toBe('new@example.com');
        expect($user->hasVerifiedEmail())->toBeFalse();
        expect($user->email_verified_at)->toBeNull();
    }

    public function test_preserves_email_verification_when_email_unchanged(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Old Name',
            'email' => 'same@example.com',
            'email_verified_at' => now(),
        ]);

        $originalVerifiedAt = $user->email_verified_at;

        expect($user->hasVerifiedEmail())->toBeTrue();

        $this->action->update($user, [
            'name' => 'New Name',
            'email' => 'same@example.com',
        ]);

        $user->refresh();

        expect($user->name)->toBe('New Name');
        expect($user->email)->toBe('same@example.com');
        expect($user->hasVerifiedEmail())->toBeTrue();
        expect($user->email_verified_at->toDateTimeString())->toBe($originalVerifiedAt->toDateTimeString());
    }

    public function test_validation_error_uses_correct_bag(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        try {
            $this->action->update($user, [
                'name' => '',
                'email' => 'invalid-email',
            ]);
        } catch (ValidationException $e) {
            expect($e->errorBag)->toBe('updateProfileInformation');
            expect($e->errors())->toHaveKeys(['name', 'email']);
        }
    }

    public function test_handles_null_photo_gracefully(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'photo' => null,
        ]);

        $user->refresh();

        expect($user->name)->toBe('Test Name');
        expect($user->email)->toBe('test@example.com');
        expect($user->profile_photo_path)->toBeNull();
    }

    public function test_handles_empty_photo_gracefully(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->action->update($user, [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ]);

        $user->refresh();

        expect($user->name)->toBe('Test Name');
        expect($user->email)->toBe('test@example.com');
        expect($user->profile_photo_path)->toBeNull();
    }
}
