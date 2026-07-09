<?php

namespace Tests\Unit\Casts;

use App\Casts\ContactCollectionCast;
use App\Models\Organization;
use App\ValueObjects\ContactCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactCollectionCastTest extends TestCase
{
    use RefreshDatabase;

    protected ContactCollectionCast $cast;

    protected Organization $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cast = new ContactCollectionCast;
        $this->model = new Organization;
    }

    public function test_get_method_handles_null_value(): void
    {
        $result = $this->cast->get($this->model, 'emails', null, []);

        $this->assertInstanceOf(ContactCollection::class, $result);
        $this->assertEmpty($result->toArray());
    }

    public function test_get_method_handles_valid_json_string(): void
    {
        $jsonString = json_encode([
            ['name' => 'John Doe', 'email' => 'john@example.test'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.test'],
        ]);

        $result = $this->cast->get($this->model, 'emails', $jsonString, []);

        $this->assertInstanceOf(ContactCollection::class, $result);
        $contacts = $result->toArray();
        $this->assertCount(2, $contacts);
        $this->assertEquals('John Doe', $contacts[0]['name']);
        $this->assertEquals('john@example.test', $contacts[0]['email']);
        $this->assertEquals('Jane Smith', $contacts[1]['name']);
        $this->assertEquals('jane@example.test', $contacts[1]['email']);
    }

    public function test_get_method_handles_invalid_json_string(): void
    {
        $invalidJson = '{"invalid": json}';

        $result = $this->cast->get($this->model, 'emails', $invalidJson, []);

        $this->assertInstanceOf(ContactCollection::class, $result);
        $this->assertEmpty($result->toArray());
    }

    public function test_get_method_handles_array_input(): void
    {
        $arrayInput = [
            ['name' => 'John Doe', 'email' => 'john@example.test'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.test'],
        ];

        $result = $this->cast->get($this->model, 'emails', $arrayInput, []);

        $this->assertInstanceOf(ContactCollection::class, $result);
        $contacts = $result->toArray();
        $this->assertCount(2, $contacts);
        $this->assertEquals('John Doe', $contacts[0]['name']);
        $this->assertEquals('john@example.test', $contacts[0]['email']);
    }

    public function test_get_method_handles_malformed_array_gracefully(): void
    {
        // Array that doesn't match ContactCollection format
        $malformedArray = ['not', 'a', 'contact', 'array'];

        $result = $this->cast->get($this->model, 'emails', $malformedArray, []);

        $this->assertInstanceOf(ContactCollection::class, $result);
        // Should return empty collection when array format is invalid
        $this->assertEmpty($result->toArray());
    }

    public function test_get_method_handles_unexpected_types(): void
    {
        // Test with integer
        $result = $this->cast->get($this->model, 'emails', 123, []);
        $this->assertInstanceOf(ContactCollection::class, $result);
        $this->assertEmpty($result->toArray());

        // Test with boolean
        $result = $this->cast->get($this->model, 'emails', true, []);
        $this->assertInstanceOf(ContactCollection::class, $result);
        $this->assertEmpty($result->toArray());

        // Test with object
        $result = $this->cast->get($this->model, 'emails', new \stdClass, []);
        $this->assertInstanceOf(ContactCollection::class, $result);
        $this->assertEmpty($result->toArray());
    }

    public function test_set_method_handles_null_value(): void
    {
        $result = $this->cast->set($this->model, 'emails', null, []);

        $this->assertEquals('[]', $result);
    }

    public function test_set_method_handles_contact_collection_instance(): void
    {
        $contacts = [
            ['name' => 'John Doe', 'email' => 'john@example.test'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.test'],
        ];
        $contactCollection = new ContactCollection($contacts);

        $result = $this->cast->set($this->model, 'emails', $contactCollection, []);

        $this->assertIsString($result);
        $decodedResult = json_decode($result, true);
        $this->assertCount(2, $decodedResult);
        $this->assertEquals('John Doe', $decodedResult[0]['name']);
        $this->assertEquals('john@example.test', $decodedResult[0]['email']);
    }

    public function test_set_method_handles_array_of_contacts(): void
    {
        $contactsArray = [
            ['name' => 'John Doe', 'email' => 'john@example.test'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.test'],
        ];

        $result = $this->cast->set($this->model, 'emails', $contactsArray, []);

        $this->assertIsString($result);
        $decodedResult = json_decode($result, true);
        $this->assertCount(2, $decodedResult);
        $this->assertEquals('John Doe', $decodedResult[0]['name']);
        $this->assertEquals('john@example.test', $decodedResult[0]['email']);
    }

    public function test_set_method_handles_simple_email_array_backward_compatibility(): void
    {
        $simpleEmails = ['john@example.test', 'jane@example.test'];

        $result = $this->cast->set($this->model, 'emails', $simpleEmails, []);

        $this->assertIsString($result);
        $decodedResult = json_decode($result, true);
        $this->assertCount(2, $decodedResult);
        $this->assertEquals('', $decodedResult[0]['name']); // Empty name for backward compatibility
        $this->assertEquals('john@example.test', $decodedResult[0]['email']);
        $this->assertEquals('', $decodedResult[1]['name']);
        $this->assertEquals('jane@example.test', $decodedResult[1]['email']);
    }

    public function test_set_method_handles_single_string_email(): void
    {
        $singleEmail = 'john@example.test';

        $result = $this->cast->set($this->model, 'emails', $singleEmail, []);

        $this->assertIsString($result);
        $decodedResult = json_decode($result, true);
        $this->assertCount(1, $decodedResult);
        $this->assertEquals('', $decodedResult[0]['name']); // Empty name
        $this->assertEquals('john@example.test', $decodedResult[0]['email']);
    }

    public function test_set_method_handles_empty_array(): void
    {
        $result = $this->cast->set($this->model, 'emails', [], []);

        $this->assertEquals('[]', $result);
    }

    public function test_set_method_handles_mixed_array_format(): void
    {
        // Array with some strings and some contact objects
        $mixedArray = [
            'john@example.test', // Simple string
            ['name' => 'Jane Smith', 'email' => 'jane@example.test'], // Contact format
        ];

        $result = $this->cast->set($this->model, 'emails', $mixedArray, []);

        $this->assertIsString($result);
        $decodedResult = json_decode($result, true);
        $this->assertCount(2, $decodedResult);

        // First should be converted from string
        $this->assertEquals('', $decodedResult[0]['name']);
        $this->assertEquals('john@example.test', $decodedResult[0]['email']);

        // Second should remain as contact format
        $this->assertEquals('Jane Smith', $decodedResult[1]['name']);
        $this->assertEquals('jane@example.test', $decodedResult[1]['email']);
    }

    public function test_set_method_handles_unexpected_value_types(): void
    {
        // Test with integer
        $result = $this->cast->set($this->model, 'emails', 123, []);
        $this->assertEquals('[]', $result);

        // Test with boolean
        $result = $this->cast->set($this->model, 'emails', true, []);
        $this->assertEquals('[]', $result);

        // Test with object
        $result = $this->cast->set($this->model, 'emails', new \stdClass, []);
        $this->assertEquals('[]', $result);
    }

    public function test_roundtrip_conversion_preserves_data(): void
    {
        $originalContacts = [
            ['name' => 'John Doe', 'email' => 'john@example.test'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.test'],
        ];

        // Convert to storage format
        $stored = $this->cast->set($this->model, 'emails', $originalContacts, []);

        // Convert back from storage
        $retrieved = $this->cast->get($this->model, 'emails', $stored, []);

        $this->assertInstanceOf(ContactCollection::class, $retrieved);
        $contacts = $retrieved->toArray();
        $this->assertCount(2, $contacts);
        $this->assertEquals($originalContacts, $contacts);
    }

    public function test_roundtrip_conversion_with_simple_emails(): void
    {
        $simpleEmails = ['john@example.test', 'jane@example.test'];

        // Convert to storage format
        $stored = $this->cast->set($this->model, 'emails', $simpleEmails, []);

        // Convert back from storage
        $retrieved = $this->cast->get($this->model, 'emails', $stored, []);

        $this->assertInstanceOf(ContactCollection::class, $retrieved);
        $contacts = $retrieved->toArray();
        $this->assertCount(2, $contacts);
        $this->assertEquals('', $contacts[0]['name']);
        $this->assertEquals('john@example.test', $contacts[0]['email']);
        $this->assertEquals('', $contacts[1]['name']);
        $this->assertEquals('jane@example.test', $contacts[1]['email']);
    }

    public function test_integration_with_eloquent_model(): void
    {
        $organization = Organization::factory()->create([
            'emails' => [
                ['name' => 'John Doe', 'email' => 'john@example.test'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.test'],
            ],
        ]);

        $this->assertInstanceOf(ContactCollection::class, $organization->emails);

        $contacts = $organization->emails->toArray();
        $this->assertCount(2, $contacts);
        $this->assertEquals('John Doe', $contacts[0]['name']);
        $this->assertEquals('john@example.test', $contacts[0]['email']);
    }

    public function test_integration_with_simple_email_strings(): void
    {
        $organization = Organization::factory()->create([
            'emails' => ['john@example.test', 'jane@example.test'],
        ]);

        $this->assertInstanceOf(ContactCollection::class, $organization->emails);

        $contacts = $organization->emails->toArray();
        $this->assertCount(2, $contacts);
        $this->assertEquals('', $contacts[0]['name']); // Empty names for simple email strings
        $this->assertEquals('john@example.test', $contacts[0]['email']);
    }

    public function test_handles_malformed_json_in_database(): void
    {
        // Simulate malformed JSON in database
        $organization = new Organization;
        $organization->forceFill(['emails' => '{malformed json}']);

        // The cast should handle this gracefully
        $emails = $organization->emails;
        $this->assertInstanceOf(ContactCollection::class, $emails);
        $this->assertEmpty($emails->toArray());
    }

    public function test_handles_legacy_data_formats(): void
    {
        // Test with legacy format that might exist in database
        $legacyJsonString = json_encode(['john@example.test', 'jane@example.test']);

        $result = $this->cast->get($this->model, 'emails', $legacyJsonString, []);

        $this->assertInstanceOf(ContactCollection::class, $result);
        // Should handle legacy simple email array format
        $contacts = $result->toArray();
        $this->assertCount(2, $contacts);
    }

    public function test_preserves_json_encoding_format(): void
    {
        $contacts = [
            ['name' => 'John Doe', 'email' => 'john@example.test'],
            ['name' => 'Jane "Quote" Smith', 'email' => 'jane@example.test'],
        ];

        $result = $this->cast->set($this->model, 'emails', $contacts, []);

        $this->assertIsString($result);
        $this->assertJson($result); // Verify it's valid JSON

        // Verify special characters are properly encoded
        $decodedResult = json_decode($result, true);
        $this->assertEquals('Jane "Quote" Smith', $decodedResult[1]['name']);
    }

    public function test_handles_empty_contact_collection(): void
    {
        $emptyCollection = new ContactCollection([]);

        $result = $this->cast->set($this->model, 'emails', $emptyCollection, []);

        $this->assertEquals('[]', $result);
    }

    public function test_handles_unicode_characters(): void
    {
        $unicodeContacts = [
            ['name' => 'José García', 'email' => 'jose@example.test'],
            ['name' => '山田太郎', 'email' => 'yamada@example.test'],
        ];

        $stored = $this->cast->set($this->model, 'emails', $unicodeContacts, []);
        $retrieved = $this->cast->get($this->model, 'emails', $stored, []);

        $contacts = $retrieved->toArray();
        $this->assertEquals('José García', $contacts[0]['name']);
        $this->assertEquals('山田太郎', $contacts[1]['name']);
    }

    public function test_error_handling_with_invalid_contact_collection_data(): void
    {
        // Test with data that would cause ContactCollection to throw exception
        $invalidData = [
            ['name' => 'Valid', 'email' => 'valid@example.test'],
            ['name' => 'Invalid', 'email' => ''], // Invalid empty email
        ];

        // The cast should handle ContactCollection exceptions gracefully
        $result = $this->cast->get($this->model, 'emails', $invalidData, []);

        $this->assertInstanceOf(ContactCollection::class, $result);
        // Should either filter out invalid entries or return empty collection
    }
}
