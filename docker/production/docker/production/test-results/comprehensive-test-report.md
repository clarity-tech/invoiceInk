# Production Container Test Results

This document contains comprehensive test results for all Docker container variants of the Laravel Invoicing application.

## Test Environment

- **Database**: PostgreSQL 17 (matching Laravel Sail configuration)
- **Test Framework**: Laravel Pest
- **Environment**: Production containers with testing database
- **Network**: Docker bridge network for container communication

## Test Methodology

1. **Container Build Verification**: Ensure container builds successfully
2. **Laravel Framework Test**: Verify Laravel can start and show version
3. **Database Migration Test**: Run all migrations on fresh PostgreSQL database
4. **Full Test Suite**: Execute complete application test suite (544 tests)

## Container Variants Tested


### invoicing-frankenphp Container

```
=== FrankenPHP + Octane TEST RESULTS ===
Generated: Fri Jul 25 17:22:08 IST 2025

--- Laravel Framework Version ---
Laravel Framework 12.20.0

--- Database Migration Test ---

   INFO  Configuration cache cleared successfully.  


   INFO  Preparing database.  

  Creating migration table ...................................... 10.87ms DONE

   INFO  Running migrations.  

  0001_01_01_000000_create_users_table ........................... 7.45ms DONE
  0001_01_01_000001_create_cache_table ........................... 6.42ms DONE
  0001_01_01_000002_create_jobs_table ............................ 8.77ms DONE
  2025_06_23_090258_create_telescope_entries_table ............... 0.10ms DONE
  2025_07_05_195847_create_locations_table ....................... 3.93ms DONE
  2025_07_05_200100_create_teams_table ........................... 5.28ms DONE
  2025_07_05_200212_create_customers_table ....................... 4.27ms DONE
  2025_07_05_200240_create_invoice_numbering_series_table ........ 9.59ms DONE
  2025_07_05_200241_create_invoices_table ........................ 7.34ms DONE
  2025_07_05_200317_create_invoice_items_table .................. 14.32ms DONE
  2025_07_07_182346_add_two_factor_columns_to_users_table ........ 3.28ms DONE
  2025_07_07_182401_create_personal_access_tokens_table .......... 4.29ms DONE
  2025_07_07_182402_create_team_user_table ....................... 3.36ms DONE
  2025_07_07_182403_create_team_invitations_table ................ 3.72ms DONE
  2025_07_08_194713_create_tax_templates_table ................... 3.36ms DONE
  2025_07_08_194821_create_customer_contacts_table ............... 2.93ms DONE
  2025_07_22_052418_add_setup_tracking_to_teams_table ............ 1.38ms DONE


--- Full Test Suite Results ---

   INFO  Configuration cache cleared successfully.  


   PASS  Tests\Unit\Actions\Fortify\CreateNewUserTest
  ✓ it can create a new user                                             0.32s  
  ✓ it creates user within database transaction                          0.06s  
  ✓ it creates a personal team for the user                              0.06s  
  ✓ it validates name is required                                        0.06s  
  ✓ it validates email is required                                       0.06s  
  ✓ it validates email format                                            0.05s  
  ✓ it validates email is unique                                         0.06s  
  ✓ it validates password with password rules                            0.05s  
  ✓ it validates password confirmation                                   0.06s  
  ✓ it validates terms when feature is enabled                           0.07s  
  ✓ it does not require terms when feature is disabled                   0.06s  
  ✓ it validates name maximum length                                     0.06s  
  ✓ it validates email maximum length                                    0.05s  
  ✓ it creates team with first name when user has multiple names         0.07s  
  ✓ it creates team with full name when user has single name             0.06s  
  ✓ it hashes password before storing                                    0.07s  
  ✓ it uses PasswordValidationRules trait                                0.05s  

   PASS  Tests\Unit\Actions\Fortify\PasswordValidationRulesTest
  ✓ it returns array of password validation rules                        0.08s  
  ✓ it includes required rule                                            0.05s  
  ✓ it includes string rule                                              0.05s  
  ✓ it includes confirmed rule                                           0.05s  
  ✓ it includes Password default rule                                    0.04s  
  ✓ it can be used by classes that implement it                          0.05s  
  ✓ it provides protected method                                         0.05s  
  ✓ it returns consistent rules                                          0.05s  

   PASS  Tests\Unit\Actions\Fortify\ResetUserPasswordTest
  ✓ it can reset user password                                           0.07s  
  ✓ it validates password is required                                    0.05s  
  ✓ it validates password with password rules                            0.09s  
  ✓ it validates password confirmation                                   0.05s  
  ✓ it hashes password before saving                                     0.06s  
  ✓ it uses force fill to update password                                0.06s  
  ✓ it validates password minimum length                                 0.05s  
  ✓ it accepts valid password with confirmation                          0.06s  
  ✓ it uses PasswordValidationRules trait                                0.06s  

   PASS  Tests\Unit\Actions\Fortify\UpdateUserPasswordTest
  ✓ it can update user password                                          0.10s  
  ✓ it validates current password is required                            0.07s  
  ✓ it validates current password is correct                             0.12s  
  ✓ it validates new password is required                                0.06s  
  ✓ it validates new password with password rules                        0.05s  
  ✓ it validates new password confirmation                               0.05s  
  ✓ it hashes new password before saving                                 0.07s  
  ✓ it uses updatePassword validation bag                                0.07s  
  ✓ it uses force fill to update password                                0.05s  
  ✓ it accepts valid password change                                     0.06s  
  ✓ it uses PasswordValidationRules trait                                0.05s  

   PASS  Tests\Unit\Actions\Fortify\UpdateUserProfileInformationTest
  ✓ can update name and email                                            0.33s  
  ✓ can update name without changing email                               0.06s  
  ✓ validates required name                                              0.05s  
  ✓ validates name max length                                            0.05s  
  ✓ validates required email                                             0.05s  
  ✓ validates email format                                               0.06s  
  ✓ validates email max length                                           0.08s  
  ✓ validates unique email                                               0.06s  
  ✓ allows same user to keep their email                                 0.06s  
  ✓ can update profile photo                                             0.14s  
  ✓ validates photo file type                                            0.07s  
  ✓ validates photo file size                                            0.06s  
  ✓ can update without photo                                             0.10s  
  ✓ resets email verification when email changes                         0.14s  
  ✓ preserves email verification when email unchanged                    0.07s  
  ✓ validation error uses correct bag                                    0.06s  
  ✓ handles null photo gracefully                                        0.10s  
  ✓ handles empty photo gracefully                                       0.10s  

   PASS  Tests\Unit\Actions\Jetstream\DeleteUserTest
  ✓ it can delete a user                                                 0.10s  
  ✓ it deletes user within database transaction                          0.07s  
  ✓ it does not delete owned organizations                               0.07s  
  ✓ it detaches user from organizations                                  0.07s  
  ✓ it deletes user profile photo                                        0.06s  
  ✓ it deletes user tokens                                               0.06s  
  ✓ it preserves multiple owned organizations                            0.07s  
  ✓ it handles user with no tokens                                       0.06s  
  ✓ it handles user with no owned organizations                          0.07s  
  ✓ it processes deletion steps in correct order                         0.05s  

   PASS  Tests\Unit\EmailCollectionCastTest
  ✓ can cast null to empty email collection                              0.06s  
  ✓ can cast json string to email collection                             0.05s  
  ✓ can cast array to email collection                                   0.05s  
  ✓ returns empty collection for invalid input                           0.05s  
  ✓ can set null value                                                   0.05s  
  ✓ can set email collection                                             0.05s  
  ✓ can set array                                                        0.05s  
  ✓ can set string as single email                                       0.05s  
  ✓ returns empty array json for invalid input                           0.05s  

   PASS  Tests\Unit\EmailCollectionTest
  ✓ can create empty email collection                                    0.07s  
  ✓ can create email collection with valid emails                        0.05s  
  ✓ filters out invalid emails during construction                       0.05s  
  ✓ trims whitespace from emails                                         0.05s  
  ✓ can add valid email                                                  0.05s  
  ✓ cannot add invalid email                                             0.05s  
  ✓ does not add duplicate emails                                        0.05s  
  ✓ can remove email                                                     0.05s  
  ✓ can get first email                                                  0.04s  
  ✓ can convert to json                                                  0.05s  
  ✓ can create from json                                                 0.04s  
  ✓ throws exception for invalid json                                    0.05s  
  ✓ can create from array                                                0.05s  
  ✓ can convert to string                                                0.05s  

   PASS  Tests\Unit\InvoiceCalculatorTest
  ✓ calculates invoice with no items                                     0.06s  
  ✓ calculates invoice with items without tax                            0.05s  
  ✓ calculates invoice with items with tax                               0.04s  
  ✓ calculates from items collection                                     0.04s  
  ✓ updates invoice totals                                               0.06s  
  ✓ invoice totals value object has zero factory method                  0.05s  
  ✓ invoice totals value object can convert to array                     0.04s  
  ✓ recalculate invoice refreshes data and updates totals                0.09s  
  ✓ recalculate invoice handles invoice with modified items              0.08s  
  ✓ recalculate invoice handles removal of items                         0.12s  
  ✓ recalculate invoice handles addition of new items                    0.09s  
  ✓ calculator works with persistent invoice models                      0.11s  
  ✓ calculator handles complex integration scenario                      0.09s  
  ✓ calculator handles zero-value items in collections                   0.04s  

   PASS  Tests\Unit\Livewire\CustomerManagerSimpleTest
  ✓ customer manager component loads                                     0.17s  
  ✓ can show and hide create form                                        0.09s  
  ✓ can manage email fields                                              0.08s  
  ✓ loads customers through computed property                            0.10s  
  ✓ can populate form for editing                                        0.10s  
  ✓ resets form correctly                                                0.09s  
  ✓ validates required fields                                            0.10s  
  ✓ can create customer with valid data                                  0.13s  
  ✓ can delete customer                                                  0.12s  

   PASS  Tests\Unit\Livewire\CustomerManagerTest
  ✓ can render customer manager component                                0.11s  
  ✓ can load customers with pagination                                   0.16s  
  ✓ can show create form                                                 0.09s  
  ✓ can add and remove email fields                                      0.10s  
  ✓ cannot remove last email field                                       0.10s  
  ✓ can create new customer with location                                0.13s  
  ✓ can create customer with multiple emails                             0.12s  
  ✓ validates required fields when creating customer                     0.10s  
  ✓ validates email format                                               0.11s  
  ✓ requires at least one non-empty email                                0.12s  
  ✓ can edit existing customer                                           0.11s  
  ✓ can update existing customer                                         0.13s  
  ✓ can delete customer                                                  0.14s  
  ✓ can cancel form                                                      0.10s  
  ✓ resets form after successful save                                    0.16s  
  ✓ handles customer without primary location when editing               0.10s  
  ✓ uses customer name plus office as default when location name is emp… 0.12s  

   PASS  Tests\Unit\Livewire\InvoiceWizardFYValidationTest
  ✓ invoice wizard shows error when using FY series without proper setu… 0.16s  
  ✓ invoice wizard creates invoice successfully with proper FY setup     0.15s  
  ✓ invoice wizard works with default series when no specific series se… 0.16s  

   PASS  Tests\Unit\Livewire\InvoiceWizardSimpleTest
  ✓ invoice wizard component loads                                       0.09s  
  ✓ initializes with correct defaults                                    0.08s  
  ✓ can show and hide create form                                        0.08s  
  ✓ can manage items                                                     0.08s  
  ✓ loads invoices through computed property                             0.18s  
  ✓ loads companies and customers                                        0.14s  
  ✓ can navigate wizard steps                                            0.14s  
  ✓ validates step 1 requirements                                        0.08s  
  ✓ calculates totals correctly                                          0.09s  
  ✓ can populate form for editing                                        0.13s  
  ✓ can create new invoice                                               0.16s  
  ✓ can create estimate                                                  0.15s  
  ✓ can delete invoice                                                   0.12s  
  ✓ generates correct invoice numbers                                    0.09s  
  ✓ loads locations based on selected entities                           0.13s  
  ✓ returns empty collections when no entity selected                    0.09s  

   PASS  Tests\Unit\Livewire\InvoiceWizardTest
  ✓ can render invoice wizard component                                  0.10s  
  ✓ initializes with default values on mount                             0.10s  
  ✓ can load invoices with pagination                                    0.30s  
  ✓ can show create form                                                 0.09s  
  ✓ can add and remove items                                             0.09s  
  ✓ cannot remove last item                                              0.08s  
  ✓ calculates totals when items are updated                             0.10s  
  ✓ can navigate between wizard steps                                    0.15s  
  ✓ validates step 1 when moving to next step                            0.09s  
  ✓ validates location selection when locations exist                    0.12s  
  ✓ advances step when valid organization and customer are selected      0.14s  
  ✓ cannot go beyond step 3 or below step 1                              0.16s  
  ✓ can create new invoice with items                                    0.22s  
  ✓ can create estimate                                                  0.16s  
  ✓ validates all fields when saving                                     0.10s  
  ✓ can edit existing invoice                                            0.11s  
  ✓ can update existing invoice                                          0.14s  
  ✓ can delete invoice                                                   0.12s  
  ✓ can delete estimate                                                  0.13s  
  ✓ can cancel form                                                      0.10s  
  ✓ resets form after successful save                                    0.16s  
  ✓ generates correct invoice number format                              0.16s  
  ✓ generates correct estimate number format                             0.16s  
  ✓ loads organization locations based on selected organization          0.11s  
  ✓ loads customer locations based on selected customer                  0.14s  
  ✓ returns empty collection when no organization selected               0.09s  
  ✓ returns empty collection when no customer selected                   0.09s  
  ✓ handles dates correctly when saving                                  0.17s  
  ✓ handles null dates when saving                                       0.18s  

   PASS  Tests\Unit\Livewire\OrganizationManagerTest
  ✓ can render organization manager component                            0.14s  
  ✓ can load organizations with pagination                               0.14s  
  ✓ can show create form                                                 0.10s  
  ✓ can add and remove email fields                                      0.10s  
  ✓ cannot remove last email field                                       0.10s  
  ✓ can create new organization with location                            0.15s  
  ✓ can create organization with multiple emails                         0.15s  
  ✓ validates required fields when creating organization                 0.10s  
  ✓ validates email format                                               0.12s  
  ✓ requires at least one non-empty email                                0.13s  
  ✓ validates currency code                                              0.12s  
  ✓ can edit existing organization                                       0.10s  
  ✓ can update existing organization                                     0.12s  
  ✓ can delete organization                                              0.11s  
  ✓ can cancel form                                                      0.11s  
  ✓ resets form after successful save                                    0.14s  
  ✓ handles organization without primary location when editing           0.11s  
  ✓ filters out empty emails when saving                                 0.14s  
  ✓ handles phone number as nullable field                               0.11s  
  ✓ handles gstin as nullable field                                      0.14s  
  ✓ validates field lengths                                              0.12s  
  ✓ loads organizations through computed property                        0.15s  
  ✓ correctly handles address line 2 as optional                         0.17s  
  ✓ uses organization name as default when location name is empty        0.14s  
  ✓ always resets financial year when country changes                    0.10s  

   PASS  Tests\Unit\Mail\DocumentMailerTest
  ✓ can create document mailer for invoice                               0.10s  
  ✓ document mailer builds correctly for invoice                         0.07s  
  ✓ document mailer has correct subject for invoice                      0.08s  
  ✓ document mailer has correct subject for estimate                     0.07s  
  ✓ document mailer implements ShouldQueue                               0.08s  
  ✓ document mailer uses correct view for invoice                        0.16s  
  ✓ document mailer uses correct view for estimate                       0.09s  
  ✓ document mailer passes correct data to view                          0.10s  
  ✓ document mailer handles different recipient emails                   0.14s  

   PASS  Tests\Unit\Models\CustomerTest
  ✓ can create customer with emails                                      0.17s  
  ✓ customer emails are cast to EmailCollection                          0.06s  
  ✓ customer can have primary location relationship                      0.09s  
  ✓ customer can have multiple locations                                 0.09s  
  ✓ customer fillable attributes work correctly                          0.06s  
  ✓ customer can be created without phone                                0.08s  
  ✓ customer emails field uses EmailCollectionCast                       0.05s  
  ✓ customer has organization relationship                               0.07s  
  ✓ customer uses HasFactory trait                                       0.05s  
  ✓ customer has correct fillable attributes                             0.06s  
  ✓ customer morphMany locations relationship works                      0.08s  
  ✓ customer primary location belongs to relationship works              0.07s  
  ✓ customer organization belongs to relationship works                  0.08s  
  ✓ customer has organization scope applied                              0.05s  
  ✓ customer can be created with all fillable attributes                 0.05s  
  ✓ customer handles empty emails collection                             0.07s  
  ✓ customer emails cast handles array input                             0.05s  
  ✓ customer emails cast handles string input                            0.06s  
  ✓ customer emails cast handles null input                              0.06s  
  ✓ customer casts method returns correct array                          0.06s  
  ✓ customer locations polymorphic relationship is configured correctly  0.08s  
  ✓ customer can have invoices through organization                      0.09s  
  ✓ customer belongs to correct organization after creation              0.07s  

   PASS  Tests\Unit\Models\InvoiceItemEdgeCaseTest
  ✓ invoice item handles very large numbers                              0.10s  
  ✓ invoice item line total calculation with zero values                 0.07s  
  ✓ invoice item line total calculation with null tax rate               0.08s  
  ✓ invoice item can be updated after creation                           0.08s  
  ✓ invoice item belongs to correct invoice after creation               0.08s  

   PASS  Tests\Unit\Models\InvoiceItemTest
  ✓ can create invoice item with all fields                              0.10s  
  ✓ invoice item belongs to invoice                                      0.08s  
  ✓ invoice item can have zero tax rate                                  0.08s  
  ✓ invoice item can have null tax rate                                  0.08s  
  ✓ invoice item fillable attributes work correctly                      0.04s  
  ✓ invoice item calculates line total correctly                         0.07s  
  ✓ invoice item handles large quantities and prices                     0.08s  
  ✓ invoice item can have fractional tax rates                           0.07s  
  ✓ invoice item has correct fillable attributes                         0.04s  
  ✓ invoice item casts method returns correct array                      0.05s  
  ✓ invoice item uses HasFactory trait                                   0.04s  
  ✓ invoice item factory creates valid instances                         0.08s  
  ✓ invoice item relationship is correctly configured                    0.04s  
  ✓ invoice item getLineTotal calculates correctly                       0.08s  
  ✓ invoice item getTaxAmount calculates correctly with tax              0.07s  
  ✓ invoice item getTaxAmount returns zero with null tax rate            0.07s  
  ✓ invoice item getTaxAmount returns zero with zero tax rate            0.07s  
  ✓ invoice item getLineTotalWithTax calculates correctly                0.07s  
  ✓ invoice item handles fractional tax calculations                     0.08s  
  ✓ invoice item handles complex tax calculations                        0.11s  
  ✓ invoice item can handle very large quantities and prices             0.06s  
  ✓ invoice item can handle zero values                                  0.05s  
  ✓ invoice item can handle zero quantity                                0.07s  
  ✓ invoice item tax rate precision is maintained                        0.07s  
  ✓ invoice item can be created without tax rate                         0.06s  
  ✓ invoice item can be updated after creation                           0.09s  
  ✓ invoice item belongs to invoice correctly                            0.09s  
  ✓ invoice item handles empty description                               0.09s  
  ✓ invoice item mass assignment works correctly                         0.10s  
  ✓ invoice item business logic methods work with edge cases             0.10s  

   PASS  Tests\Unit\Models\InvoiceNumberingSeriesTest
  ✓ can create invoice numbering series                                  0.32s  
  ✓ belongs to organization                                              0.12s  
  ✓ belongs to location                                                  0.10s  
  ✓ can have null location for organization-wide series                  0.11s  
  ✓ scopes work correctly                                                0.12s  
  ✓ should reset method works correctly                                  0.11s  
  ✓ get next sequence number works correctly                             0.11s  
  ✓ get next sequence number resets when needed                          0.10s  
  ✓ increment and save method works correctly                            0.10s  
  ✓ increment and save method updates reset timestamp when needed        0.10s  

   PASS  Tests\Unit\Models\InvoiceTest
  ✓ can create invoice with required fields                              0.06s  
  ✓ invoice automatically generates ULID on creation                     0.06s  
  ✓ invoice can be created as estimate                                   0.07s  
  ✓ invoice has organization location relationship                       0.06s  
  ✓ invoice has customer location relationship                           0.07s  
  ✓ invoice has many items relationship                                  0.06s  
  ✓ invoice type checking methods work correctly                         0.07s  
  ✓ invoice dates are cast to Carbon instances                           0.07s  
  ✓ invoice can be created without optional dates                        0.07s  
  ✓ invoice fillable attributes work correctly                           0.04s  
  ✓ invoice uses HasUlids trait                                          0.04s  
  ✓ invoice unique ids configuration                                     0.04s  
  ✓ invoice has correct fillable attributes                              0.05s  
  ✓ invoice casts method returns correct array                           0.05s  
  ✓ invoice uses HasFactory trait                                        0.06s  
  ✓ invoice factory creates valid instances                              0.08s  
  ✓ invoice has organization relationship                                0.07s  
  ✓ invoice has customer relationship                                    0.07s  
  ✓ invoice relationships are correctly configured                       0.05s  
  ✓ invoice exchange rate is cast to decimal                             0.07s  
  ✓ invoice tax breakdown is cast to json                                0.08s  
  ✓ invoice email recipients is cast to json                             0.08s  
  ✓ invoice can be created with all fillable attributes                  0.07s  
  ✓ invoice handles nullable fields correctly                            0.09s  
  ✓ invoice can be updated with new attributes                           0.12s  
  ✓ invoice ulid is automatically generated when not provided            0.08s  
  ✓ invoice can have different statuses                                  0.11s  
  ✓ invoice can handle large monetary values                             0.06s  
  ✓ invoice can handle decimal exchange rates                            0.11s  
  ✓ invoice handles complex tax breakdown structures                     0.06s  
  ✓ invoice handles complex email recipients                             0.06s  
  ✓ invoice has organization scope applied                               0.07s  
  ✓ invoice can handle empty arrays for json fields                      0.05s  

   PASS  Tests\Unit\Models\LocationTest
  ✓ can create location with all fields                                  0.06s  
  ✓ can create location with minimal required fields                     0.05s  
  ✓ location belongs to organization through polymorphic relationship    0.05s  
  ✓ location belongs to customer through polymorphic relationship        0.07s  
  ✓ location fillable attributes work correctly                          0.05s  
  ✓ location polymorphic relationship works with different models        0.08s  

   PASS  Tests\Unit\Models\MembershipTest
  ✓ it has auto-incrementing IDs enabled                                 0.06s  
  ✓ it extends JetstreamMembership                                       0.04s  
  ✓ it inherits fillable attributes from parent                          0.05s  
  ✓ it can be instantiated                                               0.05s  

   PASS  Tests\Unit\Models\OrganizationTest
  ✓ can create organization with required fields                         0.06s  
  ✓ organization extends jetstream team                                  0.04s  
  ✓ organization uses teams table                                        0.04s  
  ✓ organization has correct fillable attributes                         0.05s  
  ✓ organization emails are cast to EmailCollection                      0.05s  
  ✓ organization currency is cast to Currency enum                       0.05s  
  ✓ organization personal_team is cast to boolean                        0.06s  
  ✓ organization can have primary location relationship                  0.05s  
  ✓ organization can have multiple customers                             0.07s  
  ✓ organization can have multiple invoices                              0.08s  
  ✓ organization can have multiple tax templates                         0.07s  
  ✓ organization getUrlAttribute with custom domain                      0.06s  
  ✓ organization getUrlAttribute without custom domain                   0.07s  
  ✓ organization getDisplayNameAttribute uses company name when availab… 0.06s  
  ✓ organization getDisplayNameAttribute falls back to name when no com… 0.05s  
  ✓ organization isBusinessOrganization returns true for business organ… 0.06s  
  ✓ organization isBusinessOrganization returns false for personal team… 0.05s  
  ✓ organization isBusinessOrganization returns false when no company n… 0.05s  
  ✓ organization getCurrencySymbolAttribute returns correct symbols      0.07s  
  ✓ organization can be created with all fillable attributes             0.05s  
  ✓ organization handles empty emails collection                         0.20s  
  ✓ organization emails cast handles array input                         0.07s  
  ✓ organization emails cast handles string input                        0.06s  
  ✓ organization emails cast handles null input                          0.04s  
  ✓ organization casts method returns correct array                      0.04s  
  ✓ organization dispatches jetstream events                             0.04s  
  ✓ organization uses HasFactory trait                                   0.04s  
  ✓ organization factory creates valid instances                         0.05s  
  ✓ organization can have users relationship through jetstream           0.05s  
  ✓ organization can have team invitations relationship                  0.06s  
  ✓ organization can be updated with new attributes                      0.05s  
  ✓ organization handles nullable fields correctly                       0.07s  
  ✓ organization relationships are correctly configured                  0.04s  
  ✓ organization currency enum integration works correctly               0.07s  

   PASS  Tests\Unit\Models\TaxTemplateTest
  ✓ can create tax template with required fields                         0.05s  
  ✓ tax template has correct fillable attributes                         0.03s  
  ✓ tax template rate is cast to integer basis points                    0.05s  
  ✓ tax template is_active is cast to boolean                            0.05s  
  ✓ tax template metadata is cast to json                                0.05s  
  ✓ tax template belongs to organization                                 0.05s  
  ✓ tax template scope active filters active templates                   0.05s  
  ✓ tax template scope forCountry filters by country code                0.05s  
  ✓ tax template scope byType filters by tax type                        0.05s  
  ✓ tax template getFormattedRateAttribute returns formatted percentage  0.05s  
  ✓ tax template isGST method identifies GST types correctly             0.06s  
  ✓ tax template isVAT method identifies VAT type correctly              0.04s  
  ✓ tax template can be created with all fillable attributes             0.04s  
  ✓ tax template handles nullable fields correctly                       0.04s  
  ✓ tax template defaults is_active to true when not specified           0.05s  
  ✓ tax template can be updated                                          0.05s  
  ✓ tax template can combine multiple scopes                             0.05s  
  ✓ tax template factory creates valid instances                         0.04s  
  ✓ tax template factory gst state creates GST template                  0.05s  
  ✓ tax template factory vat state creates VAT template                  0.04s  
  ✓ tax template factory active state creates active template            0.04s  
  ✓ tax template factory inactive state creates inactive template        0.04s  
  ✓ tax template factory cgst state creates CGST template                0.04s  
  ✓ tax template factory sgst state creates SGST template                0.04s  
  ✓ tax template factory igst state creates IGST template                0.04s  
  ✓ tax template factory forCountry state sets correct country           0.04s  
  ✓ tax template factory withMetadata state sets metadata                0.06s  
  ✓ tax template casts method returns correct array                      0.04s  
  ✓ tax template uses HasFactory trait                                   0.04s  
  ✓ tax template has organization scope applied globally                 0.08s  
  ✓ tax template relationship is correctly configured                    0.04s  

   PASS  Tests\Unit\Models\TeamInvitationTest
  ✓ it can create a team invitation                                      0.09s  
  ✓ it has fillable attributes                                           0.06s  
  ✓ it belongs to a team                                                 0.06s  
  ✓ it extends JetstreamTeamInvitation                                   0.09s  
  ✓ it can have different roles                                          0.05s  
  ✓ it stores email address                                              0.05s  
  ✓ it can be mass assigned                                              0.04s  
  ✓ it has team relationship using Jetstream model                       0.04s  

   PASS  Tests\Unit\Models\UserTest
  ✓ can create user with required fields                                 0.07s  
  ✓ user email must be unique                                            0.08s  
  ✓ user has email verified at timestamp                                 0.04s  
  ✓ user can have unverified email                                       0.04s  
  ✓ user fillable attributes work correctly                              0.04s  
  ✓ user password is hidden from array output                            0.04s  
  ✓ user remember token is hidden from array output                      0.04s  
  ✓ user timestamps are cast correctly                                   0.03s  

   PASS  Tests\Unit\MoneyFormattingTest
  ✓ Invoice formats money correctly for different currencies             0.05s  
  ✓ Invoice formats money correctly for EUR currency                     0.04s  
  ✓ Invoice formats money correctly for AED currency                     0.05s  
  ✓ InvoiceItem formats money correctly for different currencies         0.05s  
  ✓ InvoiceTotals formats money correctly for different currencies       0.04s  

   PASS  Tests\Unit\PdfServiceTest
  ✓ can generate PDF for invoice                                         0.15s  
  ✓ can generate download response for invoice                           0.11s  
  ✓ can generate PDF for estimate                                        0.11s  
  ✓ can generate download response for estimate                          0.11s  
  ✓ pdf service handles invoice without items gracefully                 0.12s  
  ✓ pdf service handles empty invoice items                              0.12s  
  ✓ pdf service handles invoice with complex items                       0.10s  
  ✓ pdf service validates invoice model type                             0.07s  
  ✓ pdf service validates estimate model type                            0.07s  

   PASS  Tests\Unit\Services\EstimateToInvoiceConverterTest
  ✓ can convert estimate to invoice                                      0.11s  
  ✓ converted invoice has all items from estimate                        0.08s  
  ✓ converted invoice gets new invoice number                            0.17s  
  ✓ converted invoice has new ULID                                       0.09s  
  ✓ converter preserves dates from estimate                              0.08s  
  ✓ converter handles estimate without dates                             0.08s  
  ✓ converter works with estimates that have no items                    0.09s  
  ✓ converter preserves complex item configurations                      0.09s  
  ✓ converter throws exception when trying to convert non-estimate       0.07s  
  ✓ converter throws exception when trying to convert invoice type       0.07s  
  ✓ converter generates sequential invoice numbers for same month        0.09s  
  ✓ converter generates first invoice number when none exist             0.09s  
  ✓ converter handles estimates with null tax rates                      0.08s  
  ✓ converter handles estimates with zero tax rates                      0.09s  
  ✓ converter handles estimates with fractional tax rates                0.07s  
  ✓ converter handles estimates with large quantities and amounts        0.08s  
  ✓ converter preserves all estimate status transitions                  0.09s  
  ✓ converter handles estimates with null dates appropriately            0.06s  
  ✓ converter creates invoice with correct relationships                 0.06s  
  ✓ converter recalculates totals after conversion                       0.06s  

   PASS  Tests\Unit\Services\InvoiceCalculatorEdgeCaseTest
  ✓ invoice calculator handles mixed tax rates                           0.04s  
  ✓ invoice calculator handles fractional quantities                     0.04s  
  ✓ invoice calculator handles null tax rates                            0.04s  
  ✓ invoice calculator handles very high tax rates                       0.04s  
  ✓ invoice calculator precision with small amounts                      0.04s  
  ✓ invoice totals value object can be serialized                        0.04s  
  ✓ invoice totals zero factory creates correct object                   0.04s  

   PASS  Tests\Unit\Services\InvoiceNumberingServiceTest
  ✓ can generate invoice number with default series                      0.11s  
  ✓ can generate invoice number with existing series                     0.10s  
  ✓ can generate invoice number with location-specific series            0.10s  
  ✓ can generate invoice number with specific series name                0.09s  
  ✓ throws exception when specific series name not found                 0.10s  
  ✓ validates invoice number uniqueness                                  0.09s  
  ✓ uniqueness validation ignores estimates                              0.09s  
  ✓ can create default series                                            0.09s  
  ✓ can create location series                                           0.09s  
  ✓ format invoice number with default pattern                           0.10s  
  ✓ format invoice number with custom pattern                            0.10s  
  ✓ format invoice number with sequence padding                          0.08s  
  ✓ handles series reset correctly                                       0.09s  
  ✓ get series for organization returns correct series                   0.09s  
  ✓ concurrent number generation is thread safe                          0.10s  

   PASS  Tests\Unit\Services\PdfServiceMockTest
  ✓ pdf service can be instantiated                                      0.04s  
  ✓ pdf service generates correct filename for invoice                   0.05s  
  ✓ pdf service has correct public methods                               0.04s  
  ✓ pdf service download methods return response                         0.05s  
  ✓ pdf service handles pdf template views                               0.04s  

   WARN  Tests\Feature\ApiTokenPermissionsTest
  - api token permissions can be updated → API support is not enabled.   0.05s  

   PASS  Tests\Feature\ApplicationRoutingTest
  ✓ root route redirects to login when unauthenticated                   0.09s  
  ✓ root route redirects to dashboard when authenticated                 0.08s  
  ✓ protected routes require authentication                              0.07s  
  ✓ protected routes load successfully when authenticated                0.13s  
  ✓ dashboard loads successfully when authenticated                      0.09s  
  ✓ non-existent routes return 404                                       0.08s  

   PASS  Tests\Feature\AuthenticationTest
  ✓ login screen can be rendered                                         0.09s  
  ✓ users can authenticate using the login screen                        0.13s  
  ✓ users cannot authenticate with invalid password                      0.09s  

   PASS  Tests\Feature\BrowserSessionsTest
  ✓ other browser sessions can be logged out                             0.10s  

   WARN  Tests\Feature\CreateApiTokenTest
  - api tokens can be created → API support is not enabled.              0.05s  

   PASS  Tests\Feature\DeleteAccountTest
  ✓ user accounts can be deleted                                         0.10s  
  ✓ correct password must be provided before account can be deleted      0.09s  

   WARN  Tests\Feature\DeleteApiTokenTest
  - api tokens can be deleted → API support is not enabled.              0.05s  

   PASS  Tests\Feature\EmailVerificationTest
  ✓ email verification screen can be rendered                            0.10s  
  ✓ email can be verified                                                0.11s  
  ✓ email can not verified with invalid hash                             0.09s  

   PASS  Tests\Feature\ExampleTest
  ✓ it redirects unauthenticated users to login                          0.12s  

   PASS  Tests\Feature\FinancialYearTokenTest
  ✓ financial year tokens are replaced correctly for indian organizatio… 0.06s  
  ✓ financial year tokens work for different patterns                    0.07s  
  ✓ financial year tokens work across financial year boundary            0.06s  
  ✓ organizations without financial year setup use regular year tokens   0.06s  
  ✓ default series creation uses financial year pattern when organizati… 0.05s  

   PASS  Tests\Feature\FinancialYearValidationTest
  ✓ throws exception when FY reset frequency used without financial yea… 0.05s  
  ✓ throws exception when FY tokens used without financial year setup    0.05s  
  ✓ throws exception when FY reset frequency used without country setup  0.05s  
  ✓ validates successfully when organization has proper FY setup         0.05s  
  ✓ validation passes for non-FY series without FY setup                 0.05s  
  ✓ default series creation uses FY format only when organization has c… 0.06s  

   PASS  Tests\Feature\InvoiceNumberingIntegrationTest
  ✓ invoice creation uses numbering series correctly                     0.16s  
  ✓ estimate creation does not use numbering series                      0.13s  
  ✓ multiple invoices from same organization have sequential numbers     0.11s  
  ✓ different organizations have independent numbering series            0.16s  
  ✓ estimate to invoice converter uses numbering series                  0.12s  
  ✓ invoice number uniqueness constraint works at organization level     0.10s  
  ✓ cannot create duplicate invoice numbers within same organization     0.10s  
  ✓ can create invoice and estimate with same number in same organizati… 0.10s  

   PASS  Tests\Feature\NumberingSeriesManagerTest
  ✓ can render numbering series manager                                  0.14s  
  ✓ can create numbering series                                          0.18s  
  ✓ can edit numbering series                                            0.15s  
  ✓ can delete numbering series                                          0.14s  
  ✓ can toggle active status                                             0.13s  
  ✓ can set as default                                                   0.13s  
  ✓ validation works correctly                                           0.14s  
  ✓ next number preview works                                            0.14s  
  ✓ computed properties work                                             0.14s  

   PASS  Tests\Feature\PasswordConfirmationTest
  ✓ confirm password screen can be rendered                              0.09s  
  ✓ password can be confirmed                                            0.09s  
  ✓ password is not confirmed with invalid password                      0.32s  

   PASS  Tests\Feature\PasswordResetTest
  ✓ reset password link screen can be rendered                           0.12s  
  ✓ reset password link can be requested                                 0.31s  
  ✓ reset password screen can be rendered                                0.36s  
  ✓ password can be reset with valid token                               0.33s  

   PASS  Tests\Feature\ProfileInformationTest
  ✓ current profile information is available                             0.11s  
  ✓ profile information can be updated                                   0.13s  

   PASS  Tests\Feature\PublicViewControllerTest
  ✓ can view public invoice page                                         0.11s  
  ✓ can view public estimate page                                        0.11s  
  ✓ returns 404 for non-existent invoice                                 0.11s  
  ✓ returns 404 for non-existent estimate                                0.09s  
  ✓ returns 404 when accessing invoice with estimate ULID                0.10s  
  ✓ returns 404 when accessing estimate with invoice ULID                0.11s  
  ✓ public invoice page displays all address details                     0.10s  
  ✓ public invoice page displays multiple items correctly                0.11s  
  ✓ can download invoice PDF                                             0.10s  
  ✓ can download estimate PDF                                            0.11s  
  ✓ PDF download returns 404 for non-existent invoice                    0.10s  
  ✓ PDF download returns 404 for non-existent estimate                   0.10s  

   WARN  Tests\Feature\RegistrationTest
  ✓ registration screen can be rendered                                  0.09s  
  - registration screen cannot be rendered if support is disabled → Reg… 0.04s  
  ✓ new users can register                                               0.09s  

   PASS  Tests\Feature\TwoFactorAuthenticationSettingsTest
  ✓ two factor authentication can be enabled                             0.15s  
  ✓ recovery codes can be regenerated                                    0.14s  
  ✓ two factor authentication can be disabled                            0.12s  

   PASS  Tests\Feature\UpdatePasswordTest
  ✓ password can be updated                                              0.10s  
  ✓ current password must be correct                                     0.09s  
  ✓ new passwords must match                                             0.09s  

  Tests:    4 skipped, 544 passed (1623 assertions)
  Duration: 46.91s


--- Test Summary ---
Test Duration: 48 seconds
  Duration: 46.91s
Test Duration: 48 seconds
```

### invoicing-nginx Container

```
=== Nginx + PHP-FPM TEST RESULTS ===
Generated: Fri Jul 25 17:22:58 IST 2025

--- Laravel Framework Version ---
Laravel Framework 12.20.0

--- Database Migration Test ---

   INFO  Configuration cache cleared successfully.  


  Dropping all tables ........................................... 23.50ms DONE

   INFO  Preparing database.  

  Creating migration table ....................................... 6.46ms DONE

   INFO  Running migrations.  

  0001_01_01_000000_create_users_table ........................... 7.37ms DONE
  0001_01_01_000001_create_cache_table ........................... 4.24ms DONE
  0001_01_01_000002_create_jobs_table ............................ 6.66ms DONE
  2025_06_23_090258_create_telescope_entries_table ............... 0.22ms DONE
  2025_07_05_195847_create_locations_table ....................... 4.15ms DONE
  2025_07_05_200100_create_teams_table ........................... 4.55ms DONE
  2025_07_05_200212_create_customers_table ....................... 4.23ms DONE
  2025_07_05_200240_create_invoice_numbering_series_table ........ 4.39ms DONE
  2025_07_05_200241_create_invoices_table ........................ 5.75ms DONE
  2025_07_05_200317_create_invoice_items_table ................... 9.00ms DONE
  2025_07_07_182346_add_two_factor_columns_to_users_table ........ 2.52ms DONE
  2025_07_07_182401_create_personal_access_tokens_table .......... 2.91ms DONE
  2025_07_07_182402_create_team_user_table ....................... 2.07ms DONE
  2025_07_07_182403_create_team_invitations_table ................ 2.85ms DONE
  2025_07_08_194713_create_tax_templates_table ................... 2.64ms DONE
  2025_07_08_194821_create_customer_contacts_table ............... 2.29ms DONE
  2025_07_22_052418_add_setup_tracking_to_teams_table ............ 1.38ms DONE


--- Full Test Suite Results ---

   INFO  Configuration cache cleared successfully.  


   PASS  Tests\Unit\Actions\Fortify\CreateNewUserTest
  ✓ it can create a new user                                             0.21s  
  ✓ it creates user within database transaction                          0.07s  
  ✓ it creates a personal team for the user                              0.06s  
  ✓ it validates name is required                                        0.06s  
  ✓ it validates email is required                                       0.05s  
  ✓ it validates email format                                            0.05s  
  ✓ it validates email is unique                                         0.05s  
  ✓ it validates password with password rules                            0.05s  
  ✓ it validates password confirmation                                   0.04s  
  ✓ it validates terms when feature is enabled                           0.05s  
  ✓ it does not require terms when feature is disabled                   0.05s  
  ✓ it validates name maximum length                                     0.05s  
  ✓ it validates email maximum length                                    0.04s  
  ✓ it creates team with first name when user has multiple names         0.05s  
  ✓ it creates team with full name when user has single name             0.06s  
  ✓ it hashes password before storing                                    0.06s  
  ✓ it uses PasswordValidationRules trait                                0.04s  

   PASS  Tests\Unit\Actions\Fortify\PasswordValidationRulesTest
  ✓ it returns array of password validation rules                        0.07s  
  ✓ it includes required rule                                            0.04s  
  ✓ it includes string rule                                              0.05s  
  ✓ it includes confirmed rule                                           0.05s  
  ✓ it includes Password default rule                                    0.04s  
  ✓ it can be used by classes that implement it                          0.04s  
  ✓ it provides protected method                                         0.06s  
  ✓ it returns consistent rules                                          0.05s  

   PASS  Tests\Unit\Actions\Fortify\ResetUserPasswordTest
  ✓ it can reset user password                                           0.06s  
  ✓ it validates password is required                                    0.04s  
  ✓ it validates password with password rules                            0.05s  
  ✓ it validates password confirmation                                   0.05s  
  ✓ it hashes password before saving                                     0.05s  
  ✓ it uses force fill to update password                                0.05s  
  ✓ it validates password minimum length                                 0.05s  
  ✓ it accepts valid password with confirmation                          0.05s  
  ✓ it uses PasswordValidationRules trait                                0.05s  

   PASS  Tests\Unit\Actions\Fortify\UpdateUserPasswordTest
  ✓ it can update user password                                          0.07s  
  ✓ it validates current password is required                            0.05s  
  ✓ it validates current password is correct                             0.06s  
  ✓ it validates new password is required                                0.05s  
  ✓ it validates new password with password rules                        0.07s  
  ✓ it validates new password confirmation                               0.06s  
  ✓ it hashes new password before saving                                 0.10s  
  ✓ it uses updatePassword validation bag                                0.05s  
  ✓ it uses force fill to update password                                0.05s  
  ✓ it accepts valid password change                                     0.05s  
  ✓ it uses PasswordValidationRules trait                                0.05s  

   PASS  Tests\Unit\Actions\Fortify\UpdateUserProfileInformationTest
  ✓ can update name and email                                            0.19s  
  ✓ can update name without changing email                               0.09s  
  ✓ validates required name                                              0.05s  
  ✓ validates name max length                                            0.06s  
  ✓ validates required email                                             0.06s  
  ✓ validates email format                                               0.05s  
  ✓ validates email max length                                           0.05s  
  ✓ validates unique email                                               0.06s  
  ✓ allows same user to keep their email                                 0.05s  
  ✓ can update profile photo                                             0.10s  
  ✓ validates photo file type                                            0.05s  
  ✓ validates photo file size                                            0.05s  
  ✓ can update without photo                                             0.08s  
  ✓ resets email verification when email changes                         0.06s  
  ✓ preserves email verification when email unchanged                    0.05s  
  ✓ validation error uses correct bag                                    0.05s  
  ✓ handles null photo gracefully                                        0.06s  
  ✓ handles empty photo gracefully                                       0.07s  

   PASS  Tests\Unit\Actions\Jetstream\DeleteUserTest
  ✓ it can delete a user                                                 0.08s  
  ✓ it deletes user within database transaction                          0.05s  
  ✓ it does not delete owned organizations                               0.06s  
  ✓ it detaches user from organizations                                  0.06s  
  ✓ it deletes user profile photo                                        0.05s  
  ✓ it deletes user tokens                                               0.05s  
  ✓ it preserves multiple owned organizations                            0.05s  
  ✓ it handles user with no tokens                                       0.05s  
  ✓ it handles user with no owned organizations                          0.05s  
  ✓ it processes deletion steps in correct order                         0.05s  

   PASS  Tests\Unit\EmailCollectionCastTest
  ✓ can cast null to empty email collection                              0.06s  
  ✓ can cast json string to email collection                             0.05s  
  ✓ can cast array to email collection                                   0.04s  
  ✓ returns empty collection for invalid input                           0.04s  
  ✓ can set null value                                                   0.04s  
  ✓ can set email collection                                             0.05s  
  ✓ can set array                                                        0.05s  
  ✓ can set string as single email                                       0.04s  
  ✓ returns empty array json for invalid input                           0.04s  

   PASS  Tests\Unit\EmailCollectionTest
  ✓ can create empty email collection                                    0.05s  
  ✓ can create email collection with valid emails                        0.05s  
  ✓ filters out invalid emails during construction                       0.04s  
  ✓ trims whitespace from emails                                         0.04s  
  ✓ can add valid email                                                  0.04s  
  ✓ cannot add invalid email                                             0.05s  
  ✓ does not add duplicate emails                                        0.06s  
  ✓ can remove email                                                     0.07s  
  ✓ can get first email                                                  0.05s  
  ✓ can convert to json                                                  0.05s  
  ✓ can create from json                                                 0.05s  
  ✓ throws exception for invalid json                                    0.05s  
  ✓ can create from array                                                0.13s  
  ✓ can convert to string                                                0.06s  

   PASS  Tests\Unit\InvoiceCalculatorTest
  ✓ calculates invoice with no items                                     0.07s  
  ✓ calculates invoice with items without tax                            0.05s  
  ✓ calculates invoice with items with tax                               0.06s  
  ✓ calculates from items collection                                     0.05s  
  ✓ updates invoice totals                                               0.04s  
  ✓ invoice totals value object has zero factory method                  0.04s  
  ✓ invoice totals value object can convert to array                     0.04s  
  ✓ recalculate invoice refreshes data and updates totals                0.06s  
  ✓ recalculate invoice handles invoice with modified items              0.05s  
  ✓ recalculate invoice handles removal of items                         0.05s  
  ✓ recalculate invoice handles addition of new items                    0.05s  
  ✓ calculator works with persistent invoice models                      0.05s  
  ✓ calculator handles complex integration scenario                      0.05s  
  ✓ calculator handles zero-value items in collections                   0.05s  

   PASS  Tests\Unit\Livewire\CustomerManagerSimpleTest
  ✓ customer manager component loads                                     0.13s  
  ✓ can show and hide create form                                        0.07s  
  ✓ can manage email fields                                              0.06s  
  ✓ loads customers through computed property                            0.08s  
  ✓ can populate form for editing                                        0.07s  
  ✓ resets form correctly                                                0.07s  
  ✓ validates required fields                                            0.08s  
  ✓ can create customer with valid data                                  0.10s  
  ✓ can delete customer                                                  0.09s  

   PASS  Tests\Unit\Livewire\CustomerManagerTest
  ✓ can render customer manager component                                0.08s  
  ✓ can load customers with pagination                                   0.13s  
  ✓ can show create form                                                 0.07s  
  ✓ can add and remove email fields                                      0.07s  
  ✓ cannot remove last email field                                       0.07s  
  ✓ can create new customer with location                                0.10s  
  ✓ can create customer with multiple emails                             0.10s  
  ✓ validates required fields when creating customer                     0.08s  
  ✓ validates email format                                               0.08s  
  ✓ requires at least one non-empty email                                0.11s  
  ✓ can edit existing customer                                           0.08s  
  ✓ can update existing customer                                         0.09s  
  ✓ can delete customer                                                  0.08s  
  ✓ can cancel form                                                      0.08s  
  ✓ resets form after successful save                                    0.10s  
  ✓ handles customer without primary location when editing               0.08s  
  ✓ uses customer name plus office as default when location name is emp… 0.10s  

   PASS  Tests\Unit\Livewire\InvoiceWizardFYValidationTest
  ✓ invoice wizard shows error when using FY series without proper setu… 0.14s  
  ✓ invoice wizard creates invoice successfully with proper FY setup     0.13s  
  ✓ invoice wizard works with default series when no specific series se… 0.12s  

   PASS  Tests\Unit\Livewire\InvoiceWizardSimpleTest
  ✓ invoice wizard component loads                                       0.07s  
  ✓ initializes with correct defaults                                    0.06s  
  ✓ can show and hide create form                                        0.06s  
  ✓ can manage items                                                     0.07s  
  ✓ loads invoices through computed property                             0.08s  
  ✓ loads companies and customers                                        0.09s  
  ✓ can navigate wizard steps                                            0.11s  
  ✓ validates step 1 requirements                                        0.09s  
  ✓ calculates totals correctly                                          0.10s  
  ✓ can populate form for editing                                        0.11s  
  ✓ can create new invoice                                               0.15s  
  ✓ can create estimate                                                  0.13s  
  ✓ can delete invoice                                                   0.09s  
  ✓ generates correct invoice numbers                                    0.06s  
  ✓ loads locations based on selected entities                           0.10s  
  ✓ returns empty collections when no entity selected                    0.07s  

   PASS  Tests\Unit\Livewire\InvoiceWizardTest
  ✓ can render invoice wizard component                                  0.08s  
  ✓ initializes with default values on mount                             0.07s  
  ✓ can load invoices with pagination                                    0.30s  
  ✓ can show create form                                                 0.06s  
  ✓ can add and remove items                                             0.06s  
  ✓ cannot remove last item                                              0.06s  
  ✓ calculates totals when items are updated                             0.07s  
  ✓ can navigate between wizard steps                                    0.12s  
  ✓ validates step 1 when moving to next step                            0.06s  
  ✓ validates location selection when locations exist                    0.11s  
  ✓ advances step when valid organization and customer are selected      0.12s  
  ✓ cannot go beyond step 3 or below step 1                              0.13s  
  ✓ can create new invoice with items                                    0.16s  
  ✓ can create estimate                                                  0.13s  
  ✓ validates all fields when saving                                     0.08s  
  ✓ can edit existing invoice                                            0.09s  
  ✓ can update existing invoice                                          0.11s  
  ✓ can delete invoice                                                   0.10s  
  ✓ can delete estimate                                                  0.09s  
  ✓ can cancel form                                                      0.07s  
  ✓ resets form after successful save                                    0.12s  
  ✓ generates correct invoice number format                              0.14s  
  ✓ generates correct estimate number format                             0.12s  
  ✓ loads organization locations based on selected organization          0.08s  
  ✓ loads customer locations based on selected customer                  0.09s  
  ✓ returns empty collection when no organization selected               0.06s  
  ✓ returns empty collection when no customer selected                   0.06s  
  ✓ handles dates correctly when saving                                  0.14s  
  ✓ handles null dates when saving                                       0.12s  

   PASS  Tests\Unit\Livewire\OrganizationManagerTest
  ✓ can render organization manager component                            0.09s  
  ✓ can load organizations with pagination                               0.12s  
  ✓ can show create form                                                 0.07s  
  ✓ can add and remove email fields                                      0.07s  
  ✓ cannot remove last email field                                       0.07s  
  ✓ can create new organization with location                            0.11s  
  ✓ can create organization with multiple emails                         0.12s  
  ✓ validates required fields when creating organization                 0.08s  
  ✓ validates email format                                               0.09s  
  ✓ requires at least one non-empty email                                0.09s  
  ✓ validates currency code                                              0.09s  
  ✓ can edit existing organization                                       0.08s  
  ✓ can update existing organization                                     0.10s  
  ✓ can delete organization                                              0.10s  
  ✓ can cancel form                                                      0.08s  
  ✓ resets form after successful save                                    0.10s  
  ✓ handles organization without primary location when editing           0.07s  
  ✓ filters out empty emails when saving                                 0.11s  
  ✓ handles phone number as nullable field                               0.09s  
  ✓ handles gstin as nullable field                                      0.11s  
  ✓ validates field lengths                                              0.10s  
  ✓ loads organizations through computed property                        0.08s  
  ✓ correctly handles address line 2 as optional                         0.10s  
  ✓ uses organization name as default when location name is empty        0.10s  
  ✓ always resets financial year when country changes                    0.07s  

   PASS  Tests\Unit\Mail\DocumentMailerTest
  ✓ can create document mailer for invoice                               0.09s  
  ✓ document mailer builds correctly for invoice                         0.06s  
  ✓ document mailer has correct subject for invoice                      0.06s  
  ✓ document mailer has correct subject for estimate                     0.06s  
  ✓ document mailer implements ShouldQueue                               0.08s  
  ✓ document mailer uses correct view for invoice                        0.07s  
  ✓ document mailer uses correct view for estimate                       0.06s  
  ✓ document mailer passes correct data to view                          0.06s  
  ✓ document mailer handles different recipient emails                   0.06s  

   PASS  Tests\Unit\Models\CustomerTest
  ✓ can create customer with emails                                      0.07s  
  ✓ customer emails are cast to EmailCollection                          0.04s  
  ✓ customer can have primary location relationship                      0.06s  
  ✓ customer can have multiple locations                                 0.06s  
  ✓ customer fillable attributes work correctly                          0.04s  
  ✓ customer can be created without phone                                0.05s  
  ✓ customer emails field uses EmailCollectionCast                       0.04s  
  ✓ customer has organization relationship                               0.06s  
  ✓ customer uses HasFactory trait                                       0.04s  
  ✓ customer has correct fillable attributes                             0.09s  
  ✓ customer morphMany locations relationship works                      0.07s  
  ✓ customer primary location belongs to relationship works              0.06s  
  ✓ customer organization belongs to relationship works                  0.06s  
  ✓ customer has organization scope applied                              0.04s  
  ✓ customer can be created with all fillable attributes                 0.04s  
  ✓ customer handles empty emails collection                             0.06s  
  ✓ customer emails cast handles array input                             0.04s  
  ✓ customer emails cast handles string input                            0.04s  
  ✓ customer emails cast handles null input                              0.04s  
  ✓ customer casts method returns correct array                          0.04s  
  ✓ customer locations polymorphic relationship is configured correctly  0.06s  
  ✓ customer can have invoices through organization                      0.08s  
  ✓ customer belongs to correct organization after creation              0.08s  

   PASS  Tests\Unit\Models\InvoiceItemEdgeCaseTest
  ✓ invoice item handles very large numbers                              0.08s  
  ✓ invoice item line total calculation with zero values                 0.07s  
  ✓ invoice item line total calculation with null tax rate               0.06s  
  ✓ invoice item can be updated after creation                           0.06s  
  ✓ invoice item belongs to correct invoice after creation               0.06s  

   PASS  Tests\Unit\Models\InvoiceItemTest
  ✓ can create invoice item with all fields                              0.07s  
  ✓ invoice item belongs to invoice                                      0.06s  
  ✓ invoice item can have zero tax rate                                  0.06s  
  ✓ invoice item can have null tax rate                                  0.05s  
  ✓ invoice item fillable attributes work correctly                      0.03s  
  ✓ invoice item calculates line total correctly                         0.06s  
  ✓ invoice item handles large quantities and prices                     0.06s  
  ✓ invoice item can have fractional tax rates                           0.07s  
  ✓ invoice item has correct fillable attributes                         0.04s  
  ✓ invoice item casts method returns correct array                      0.04s  
  ✓ invoice item uses HasFactory trait                                   0.04s  
  ✓ invoice item factory creates valid instances                         0.07s  
  ✓ invoice item relationship is correctly configured                    0.04s  
  ✓ invoice item getLineTotal calculates correctly                       0.07s  
  ✓ invoice item getTaxAmount calculates correctly with tax              0.08s  
  ✓ invoice item getTaxAmount returns zero with null tax rate            0.08s  
  ✓ invoice item getTaxAmount returns zero with zero tax rate            0.07s  
  ✓ invoice item getLineTotalWithTax calculates correctly                0.07s  
  ✓ invoice item handles fractional tax calculations                     0.07s  
  ✓ invoice item handles complex tax calculations                        0.10s  
  ✓ invoice item can handle very large quantities and prices             0.06s  
  ✓ invoice item can handle zero values                                  0.05s  
  ✓ invoice item can handle zero quantity                                0.05s  
  ✓ invoice item tax rate precision is maintained                        0.06s  
  ✓ invoice item can be created without tax rate                         0.05s  
  ✓ invoice item can be updated after creation                           0.05s  
  ✓ invoice item belongs to invoice correctly                            0.05s  
  ✓ invoice item handles empty description                               0.05s  
  ✓ invoice item mass assignment works correctly                         0.05s  
  ✓ invoice item business logic methods work with edge cases             0.06s  

   PASS  Tests\Unit\Models\InvoiceNumberingSeriesTest
  ✓ can create invoice numbering series                                  0.30s  
  ✓ belongs to organization                                              0.09s  
  ✓ belongs to location                                                  0.10s  
  ✓ can have null location for organization-wide series                  0.09s  
  ✓ scopes work correctly                                                0.09s  
  ✓ should reset method works correctly                                  0.11s  
  ✓ get next sequence number works correctly                             0.12s  
  ✓ get next sequence number resets when needed                          0.11s  
  ✓ increment and save method works correctly                            0.09s  
  ✓ increment and save method updates reset timestamp when needed        0.09s  

   PASS  Tests\Unit\Models\InvoiceTest
  ✓ can create invoice with required fields                              0.06s  
  ✓ invoice automatically generates ULID on creation                     0.05s  
  ✓ invoice can be created as estimate                                   0.05s  
  ✓ invoice has organization location relationship                       0.06s  
  ✓ invoice has customer location relationship                           0.07s  
  ✓ invoice has many items relationship                                  0.06s  
  ✓ invoice type checking methods work correctly                         0.07s  
  ✓ invoice dates are cast to Carbon instances                           0.05s  
  ✓ invoice can be created without optional dates                        0.06s  
  ✓ invoice fillable attributes work correctly                           0.04s  
  ✓ invoice uses HasUlids trait                                          0.04s  
  ✓ invoice unique ids configuration                                     0.04s  
  ✓ invoice has correct fillable attributes                              0.04s  
  ✓ invoice casts method returns correct array                           0.05s  
  ✓ invoice uses HasFactory trait                                        0.06s  
  ✓ invoice factory creates valid instances                              0.06s  
  ✓ invoice has organization relationship                                0.07s  
  ✓ invoice has customer relationship                                    0.06s  
  ✓ invoice relationships are correctly configured                       0.04s  
  ✓ invoice exchange rate is cast to decimal                             0.07s  
  ✓ invoice tax breakdown is cast to json                                0.07s  
  ✓ invoice email recipients is cast to json                             0.07s  
  ✓ invoice can be created with all fillable attributes                  0.06s  
  ✓ invoice handles nullable fields correctly                            0.07s  
  ✓ invoice can be updated with new attributes                           0.07s  
  ✓ invoice ulid is automatically generated when not provided            0.07s  
  ✓ invoice can have different statuses                                  0.10s  
  ✓ invoice can handle large monetary values                             0.06s  
  ✓ invoice can handle decimal exchange rates                            0.11s  
  ✓ invoice handles complex tax breakdown structures                     0.05s  
  ✓ invoice handles complex email recipients                             0.05s  
  ✓ invoice has organization scope applied                               0.08s  
  ✓ invoice can handle empty arrays for json fields                      0.05s  

   PASS  Tests\Unit\Models\LocationTest
  ✓ can create location with all fields                                  0.06s  
  ✓ can create location with minimal required fields                     0.04s  
  ✓ location belongs to organization through polymorphic relationship    0.05s  
  ✓ location belongs to customer through polymorphic relationship        0.05s  
  ✓ location fillable attributes work correctly                          0.04s  
  ✓ location polymorphic relationship works with different models        0.07s  

   PASS  Tests\Unit\Models\MembershipTest
  ✓ it has auto-incrementing IDs enabled                                 0.04s  
  ✓ it extends JetstreamMembership                                       0.04s  
  ✓ it inherits fillable attributes from parent                          0.04s  
  ✓ it can be instantiated                                               0.04s  

   PASS  Tests\Unit\Models\OrganizationTest
  ✓ can create organization with required fields                         0.05s  
  ✓ organization extends jetstream team                                  0.04s  
  ✓ organization uses teams table                                        0.04s  
  ✓ organization has correct fillable attributes                         0.04s  
  ✓ organization emails are cast to EmailCollection                      0.04s  
  ✓ organization currency is cast to Currency enum                       0.05s  
  ✓ organization personal_team is cast to boolean                        0.05s  
  ✓ organization can have primary location relationship                  0.05s  
  ✓ organization can have multiple customers                             0.06s  
  ✓ organization can have multiple invoices                              0.07s  
  ✓ organization can have multiple tax templates                         0.06s  
  ✓ organization getUrlAttribute with custom domain                      0.05s  
  ✓ organization getUrlAttribute without custom domain                   0.05s  
  ✓ organization getDisplayNameAttribute uses company name when availab… 0.05s  
  ✓ organization getDisplayNameAttribute falls back to name when no com… 0.05s  
  ✓ organization isBusinessOrganization returns true for business organ… 0.05s  
  ✓ organization isBusinessOrganization returns false for personal team… 0.04s  
  ✓ organization isBusinessOrganization returns false when no company n… 0.05s  
  ✓ organization getCurrencySymbolAttribute returns correct symbols      0.06s  
  ✓ organization can be created with all fillable attributes             0.04s  
  ✓ organization handles empty emails collection                         0.05s  
  ✓ organization emails cast handles array input                         0.05s  
  ✓ organization emails cast handles string input                        0.04s  
  ✓ organization emails cast handles null input                          0.05s  
  ✓ organization casts method returns correct array                      0.04s  
  ✓ organization dispatches jetstream events                             0.04s  
  ✓ organization uses HasFactory trait                                   0.06s  
  ✓ organization factory creates valid instances                         0.05s  
  ✓ organization can have users relationship through jetstream           0.05s  
  ✓ organization can have team invitations relationship                  0.06s  
  ✓ organization can be updated with new attributes                      0.07s  
  ✓ organization handles nullable fields correctly                       0.08s  
  ✓ organization relationships are correctly configured                  0.05s  
  ✓ organization currency enum integration works correctly               0.08s  

   PASS  Tests\Unit\Models\TaxTemplateTest
  ✓ can create tax template with required fields                         0.07s  
  ✓ tax template has correct fillable attributes                         0.04s  
  ✓ tax template rate is cast to integer basis points                    0.06s  
  ✓ tax template is_active is cast to boolean                            0.06s  
  ✓ tax template metadata is cast to json                                0.06s  
  ✓ tax template belongs to organization                                 0.06s  
  ✓ tax template scope active filters active templates                   0.06s  
  ✓ tax template scope forCountry filters by country code                0.06s  
  ✓ tax template scope byType filters by tax type                        0.07s  
  ✓ tax template getFormattedRateAttribute returns formatted percentage  0.06s  
  ✓ tax template isGST method identifies GST types correctly             0.07s  
  ✓ tax template isVAT method identifies VAT type correctly              0.06s  
  ✓ tax template can be created with all fillable attributes             0.05s  
  ✓ tax template handles nullable fields correctly                       0.06s  
  ✓ tax template defaults is_active to true when not specified           0.06s  
  ✓ tax template can be updated                                          0.06s  
  ✓ tax template can combine multiple scopes                             0.09s  
  ✓ tax template factory creates valid instances                         0.05s  
  ✓ tax template factory gst state creates GST template                  0.05s  
  ✓ tax template factory vat state creates VAT template                  0.05s  
  ✓ tax template factory active state creates active template            0.05s  
  ✓ tax template factory inactive state creates inactive template        0.05s  
  ✓ tax template factory cgst state creates CGST template                0.05s  
  ✓ tax template factory sgst state creates SGST template                0.05s  
  ✓ tax template factory igst state creates IGST template                0.05s  
  ✓ tax template factory forCountry state sets correct country           0.05s  
  ✓ tax template factory withMetadata state sets metadata                0.05s  
  ✓ tax template casts method returns correct array                      0.04s  
  ✓ tax template uses HasFactory trait                                   0.04s  
  ✓ tax template has organization scope applied globally                 0.08s  
  ✓ tax template relationship is correctly configured                    0.04s  

   PASS  Tests\Unit\Models\TeamInvitationTest
  ✓ it can create a team invitation                                      0.06s  
  ✓ it has fillable attributes                                           0.05s  
  ✓ it belongs to a team                                                 0.05s  
  ✓ it extends JetstreamTeamInvitation                                   0.05s  
  ✓ it can have different roles                                          0.06s  
  ✓ it stores email address                                              0.05s  
  ✓ it can be mass assigned                                              0.05s  
  ✓ it has team relationship using Jetstream model                       0.05s  

   PASS  Tests\Unit\Models\UserTest
  ✓ can create user with required fields                                 0.04s  
  ✓ user email must be unique                                            0.05s  
  ✓ user has email verified at timestamp                                 0.05s  
  ✓ user can have unverified email                                       0.05s  
  ✓ user fillable attributes work correctly                              0.05s  
  ✓ user password is hidden from array output                            0.05s  
  ✓ user remember token is hidden from array output                      0.05s  
  ✓ user timestamps are cast correctly                                   0.07s  

   PASS  Tests\Unit\MoneyFormattingTest
  ✓ Invoice formats money correctly for different currencies             0.07s  
  ✓ Invoice formats money correctly for EUR currency                     0.08s  
  ✓ Invoice formats money correctly for AED currency                     0.06s  
  ✓ InvoiceItem formats money correctly for different currencies         0.07s  
  ✓ InvoiceTotals formats money correctly for different currencies       0.04s  

   PASS  Tests\Unit\PdfServiceTest
  ✓ can generate PDF for invoice                                         0.11s  
  ✓ can generate download response for invoice                           0.10s  
  ✓ can generate PDF for estimate                                        0.09s  
  ✓ can generate download response for estimate                          0.09s  
  ✓ pdf service handles invoice without items gracefully                 0.09s  
  ✓ pdf service handles empty invoice items                              0.08s  
  ✓ pdf service handles invoice with complex items                       0.09s  
  ✓ pdf service validates invoice model type                             0.06s  
  ✓ pdf service validates estimate model type                            0.06s  

   PASS  Tests\Unit\Services\EstimateToInvoiceConverterTest
  ✓ can convert estimate to invoice                                      0.08s  
  ✓ converted invoice has all items from estimate                        0.06s  
  ✓ converted invoice gets new invoice number                            0.06s  
  ✓ converted invoice has new ULID                                       0.06s  
  ✓ converter preserves dates from estimate                              0.07s  
  ✓ converter handles estimate without dates                             0.07s  
  ✓ converter works with estimates that have no items                    0.06s  
  ✓ converter preserves complex item configurations                      0.06s  
  ✓ converter throws exception when trying to convert non-estimate       0.05s  
  ✓ converter throws exception when trying to convert invoice type       0.05s  
  ✓ converter generates sequential invoice numbers for same month        0.07s  
  ✓ converter generates first invoice number when none exist             0.06s  
  ✓ converter handles estimates with null tax rates                      0.07s  
  ✓ converter handles estimates with zero tax rates                      0.06s  
  ✓ converter handles estimates with fractional tax rates                0.06s  
  ✓ converter handles estimates with large quantities and amounts        0.08s  
  ✓ converter preserves all estimate status transitions                  0.18s  
  ✓ converter handles estimates with null dates appropriately            0.08s  
  ✓ converter creates invoice with correct relationships                 0.07s  
  ✓ converter recalculates totals after conversion                       0.08s  

   PASS  Tests\Unit\Services\InvoiceCalculatorEdgeCaseTest
  ✓ invoice calculator handles mixed tax rates                           0.05s  
  ✓ invoice calculator handles fractional quantities                     0.04s  
  ✓ invoice calculator handles null tax rates                            0.07s  
  ✓ invoice calculator handles very high tax rates                       0.04s  
  ✓ invoice calculator precision with small amounts                      0.04s  
  ✓ invoice totals value object can be serialized                        0.04s  
  ✓ invoice totals zero factory creates correct object                   0.04s  

   PASS  Tests\Unit\Services\InvoiceNumberingServiceTest
  ✓ can generate invoice number with default series                      0.11s  
  ✓ can generate invoice number with existing series                     0.09s  
  ✓ can generate invoice number with location-specific series            0.09s  
  ✓ can generate invoice number with specific series name                0.38s  
  ✓ throws exception when specific series name not found                 0.15s  
  ✓ validates invoice number uniqueness                                  0.09s  
  ✓ uniqueness validation ignores estimates                              0.10s  
  ✓ can create default series                                            0.08s  
  ✓ can create location series                                           0.09s  
  ✓ format invoice number with default pattern                           0.08s  
  ✓ format invoice number with custom pattern                            0.08s  
  ✓ format invoice number with sequence padding                          0.08s  
  ✓ handles series reset correctly                                       0.09s  
  ✓ get series for organization returns correct series                   0.08s  
  ✓ concurrent number generation is thread safe                          0.10s  

   PASS  Tests\Unit\Services\PdfServiceMockTest
  ✓ pdf service can be instantiated                                      0.04s  
  ✓ pdf service generates correct filename for invoice                   0.06s  
  ✓ pdf service has correct public methods                               0.04s  
  ✓ pdf service download methods return response                         0.05s  
  ✓ pdf service handles pdf template views                               0.04s  

   WARN  Tests\Feature\ApiTokenPermissionsTest
  - api token permissions can be updated → API support is not enabled.   0.07s  

   PASS  Tests\Feature\ApplicationRoutingTest
  ✓ root route redirects to login when unauthenticated                   0.06s  
  ✓ root route redirects to dashboard when authenticated                 0.07s  
  ✓ protected routes require authentication                              0.06s  
  ✓ protected routes load successfully when authenticated                0.09s  
  ✓ dashboard loads successfully when authenticated                      0.07s  
  ✓ non-existent routes return 404                                       0.06s  

   PASS  Tests\Feature\AuthenticationTest
  ✓ login screen can be rendered                                         0.07s  
  ✓ users can authenticate using the login screen                        0.07s  
  ✓ users cannot authenticate with invalid password                      0.06s  

   PASS  Tests\Feature\BrowserSessionsTest
  ✓ other browser sessions can be logged out                             0.07s  

   WARN  Tests\Feature\CreateApiTokenTest
  - api tokens can be created → API support is not enabled.              0.04s  

   PASS  Tests\Feature\DeleteAccountTest
  ✓ user accounts can be deleted                                         0.08s  
  ✓ correct password must be provided before account can be deleted      0.06s  

   WARN  Tests\Feature\DeleteApiTokenTest
  - api tokens can be deleted → API support is not enabled.              0.04s  

   PASS  Tests\Feature\EmailVerificationTest
  ✓ email verification screen can be rendered                            0.06s  
  ✓ email can be verified                                                0.06s  
  ✓ email can not verified with invalid hash                             0.06s  

   PASS  Tests\Feature\ExampleTest
  ✓ it redirects unauthenticated users to login                          0.06s  

   PASS  Tests\Feature\FinancialYearTokenTest
  ✓ financial year tokens are replaced correctly for indian organizatio… 0.05s  
  ✓ financial year tokens work for different patterns                    0.06s  
  ✓ financial year tokens work across financial year boundary            0.06s  
  ✓ organizations without financial year setup use regular year tokens   0.06s  
  ✓ default series creation uses financial year pattern when organizati… 0.05s  

   PASS  Tests\Feature\FinancialYearValidationTest
  ✓ throws exception when FY reset frequency used without financial yea… 0.06s  
  ✓ throws exception when FY tokens used without financial year setup    0.05s  
  ✓ throws exception when FY reset frequency used without country setup  0.06s  
  ✓ validates successfully when organization has proper FY setup         0.05s  
  ✓ validation passes for non-FY series without FY setup                 0.05s  
  ✓ default series creation uses FY format only when organization has c… 0.06s  

   PASS  Tests\Feature\InvoiceNumberingIntegrationTest
  ✓ invoice creation uses numbering series correctly                     0.10s  
  ✓ estimate creation does not use numbering series                      0.12s  
  ✓ multiple invoices from same organization have sequential numbers     0.10s  
  ✓ different organizations have independent numbering series            0.09s  
  ✓ estimate to invoice converter uses numbering series                  0.09s  
  ✓ invoice number uniqueness constraint works at organization level     0.09s  
  ✓ cannot create duplicate invoice numbers within same organization     0.09s  
  ✓ can create invoice and estimate with same number in same organizati… 0.09s  

   PASS  Tests\Feature\NumberingSeriesManagerTest
  ✓ can render numbering series manager                                  0.10s  
  ✓ can create numbering series                                          0.18s  
  ✓ can edit numbering series                                            0.17s  
  ✓ can delete numbering series                                          0.12s  
  ✓ can toggle active status                                             0.13s  
  ✓ can set as default                                                   0.13s  
  ✓ validation works correctly                                           0.13s  
  ✓ next number preview works                                            0.14s  
  ✓ computed properties work                                             0.14s  

   PASS  Tests\Feature\PasswordConfirmationTest
  ✓ confirm password screen can be rendered                              0.06s  
  ✓ password can be confirmed                                            0.06s  
  ✓ password is not confirmed with invalid password                      0.28s  

   PASS  Tests\Feature\PasswordResetTest
  ✓ reset password link screen can be rendered                           0.13s  
  ✓ reset password link can be requested                                 0.28s  
  ✓ reset password screen can be rendered                                0.33s  
  ✓ password can be reset with valid token                               0.36s  

   PASS  Tests\Feature\ProfileInformationTest
  ✓ current profile information is available                             0.12s  
  ✓ profile information can be updated                                   0.08s  

   PASS  Tests\Feature\PublicViewControllerTest
  ✓ can view public invoice page                                         0.13s  
  ✓ can view public estimate page                                        0.12s  
  ✓ returns 404 for non-existent invoice                                 0.08s  
  ✓ returns 404 for non-existent estimate                                0.08s  
  ✓ returns 404 when accessing invoice with estimate ULID                0.08s  
  ✓ returns 404 when accessing estimate with invoice ULID                0.10s  
  ✓ public invoice page displays all address details                     0.08s  
  ✓ public invoice page displays multiple items correctly                0.09s  
  ✓ can download invoice PDF                                             0.08s  
  ✓ can download estimate PDF                                            0.09s  
  ✓ PDF download returns 404 for non-existent invoice                    0.08s  
  ✓ PDF download returns 404 for non-existent estimate                   0.08s  

   WARN  Tests\Feature\RegistrationTest
  ✓ registration screen can be rendered                                  0.06s  
  - registration screen cannot be rendered if support is disabled → Reg… 0.05s  
  ✓ new users can register                                               0.08s  

   PASS  Tests\Feature\TwoFactorAuthenticationSettingsTest
  ✓ two factor authentication can be enabled                             0.12s  
  ✓ recovery codes can be regenerated                                    0.13s  
  ✓ two factor authentication can be disabled                            0.09s  

   PASS  Tests\Feature\UpdatePasswordTest
  ✓ password can be updated                                              0.07s  
  ✓ current password must be correct                                     0.08s  
  ✓ new passwords must match                                             0.07s  

  Tests:    4 skipped, 544 passed (1623 assertions)
  Duration: 39.99s


--- Test Summary ---
Test Duration: 41 seconds
  Duration: 39.99s
Test Duration: 41 seconds
```

## Test Execution Log

```
2025-07-25 17:10:47 - INFO: Starting comprehensive container testing...
2025-07-25 17:10:47 - INFO: Setting up PostgreSQL test database...
2025-07-25 17:10:48 - INFO: Waiting for PostgreSQL to be ready...
2025-07-25 17:11:04 - SUCCESS: PostgreSQL setup completed
2025-07-25 17:11:04 - INFO: Testing FrankenPHP + Octane...
2025-07-25 17:11:05 - SUCCESS: FrankenPHP + Octane - Laravel version check passed
2025-07-25 17:11:07 - SUCCESS: FrankenPHP + Octane - Database migrations passed
2025-07-25 17:12:01 - SUCCESS: FrankenPHP + Octane - Full test suite passed in 54s
2025-07-25 17:12:01 - INFO: Nginx container not available for testing
2025-07-25 17:12:01 - INFO: Standalone container not available for testing
2025-07-25 17:12:01 - SUCCESS: Comprehensive test report generated: docker/production/test-results/comprehensive-test-report.md
2025-07-25 17:12:01 - INFO: === FINAL TEST SUMMARY ===
2025-07-25 17:12:01 - INFO: Containers Tested: 1
2025-07-25 17:12:01 - SUCCESS: Containers Passed: 1
2025-07-25 17:12:01 - SUCCESS: 🎉 All available containers passed testing!
2025-07-25 17:12:02 - INFO: Test results saved in: docker/production/test-results/
2025-07-25 17:12:02 - INFO: Cleaning up test infrastructure...
2025-07-25 17:12:04 - SUCCESS: Cleanup completed
2025-07-25 17:21:52 - INFO: Starting comprehensive container testing...
2025-07-25 17:21:52 - INFO: Setting up PostgreSQL test database...
2025-07-25 17:21:52 - INFO: Waiting for PostgreSQL to be ready...
2025-07-25 17:22:08 - SUCCESS: PostgreSQL setup completed
2025-07-25 17:22:08 - INFO: Testing FrankenPHP + Octane...
2025-07-25 17:22:09 - SUCCESS: FrankenPHP + Octane - Laravel version check passed
2025-07-25 17:22:10 - SUCCESS: FrankenPHP + Octane - Database migrations passed
2025-07-25 17:22:58 - SUCCESS: FrankenPHP + Octane - Full test suite passed in 48s
2025-07-25 17:22:58 - INFO: Testing Nginx + PHP-FPM...
2025-07-25 17:22:59 - SUCCESS: Nginx + PHP-FPM - Laravel version check passed
2025-07-25 17:23:00 - SUCCESS: Nginx + PHP-FPM - Database migrations passed
2025-07-25 17:23:41 - SUCCESS: Nginx + PHP-FPM - Full test suite passed in 41s
2025-07-25 17:23:41 - INFO: Standalone container not available for testing
```
