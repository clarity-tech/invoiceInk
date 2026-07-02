<?php

namespace App\Enums;

/**
 * Indian states/UTs keyed by their GST state code (the first two digits of a
 * GSTIN). Used for the "Place of Supply" on GST invoices.
 */
enum IndianState: string
{
    case JammuAndKashmir = '01';
    case HimachalPradesh = '02';
    case Punjab = '03';
    case Chandigarh = '04';
    case Uttarakhand = '05';
    case Haryana = '06';
    case Delhi = '07';
    case Rajasthan = '08';
    case UttarPradesh = '09';
    case Bihar = '10';
    case Sikkim = '11';
    case ArunachalPradesh = '12';
    case Nagaland = '13';
    case Manipur = '14';
    case Mizoram = '15';
    case Tripura = '16';
    case Meghalaya = '17';
    case Assam = '18';
    case WestBengal = '19';
    case Jharkhand = '20';
    case Odisha = '21';
    case Chhattisgarh = '22';
    case MadhyaPradesh = '23';
    case Gujarat = '24';
    case DadraAndNagarHaveliAndDamanAndDiu = '26';
    case Maharashtra = '27';
    case Karnataka = '29';
    case Goa = '30';
    case Lakshadweep = '31';
    case Kerala = '32';
    case TamilNadu = '33';
    case Puducherry = '34';
    case AndamanAndNicobarIslands = '35';
    case Telangana = '36';
    case AndhraPradesh = '37';
    case Ladakh = '38';
    case OtherTerritory = '97';

    public function label(): string
    {
        return match ($this) {
            self::JammuAndKashmir => 'Jammu and Kashmir',
            self::HimachalPradesh => 'Himachal Pradesh',
            self::Punjab => 'Punjab',
            self::Chandigarh => 'Chandigarh',
            self::Uttarakhand => 'Uttarakhand',
            self::Haryana => 'Haryana',
            self::Delhi => 'Delhi',
            self::Rajasthan => 'Rajasthan',
            self::UttarPradesh => 'Uttar Pradesh',
            self::Bihar => 'Bihar',
            self::Sikkim => 'Sikkim',
            self::ArunachalPradesh => 'Arunachal Pradesh',
            self::Nagaland => 'Nagaland',
            self::Manipur => 'Manipur',
            self::Mizoram => 'Mizoram',
            self::Tripura => 'Tripura',
            self::Meghalaya => 'Meghalaya',
            self::Assam => 'Assam',
            self::WestBengal => 'West Bengal',
            self::Jharkhand => 'Jharkhand',
            self::Odisha => 'Odisha',
            self::Chhattisgarh => 'Chhattisgarh',
            self::MadhyaPradesh => 'Madhya Pradesh',
            self::Gujarat => 'Gujarat',
            self::DadraAndNagarHaveliAndDamanAndDiu => 'Dadra and Nagar Haveli and Daman and Diu',
            self::Maharashtra => 'Maharashtra',
            self::Karnataka => 'Karnataka',
            self::Goa => 'Goa',
            self::Lakshadweep => 'Lakshadweep',
            self::Kerala => 'Kerala',
            self::TamilNadu => 'Tamil Nadu',
            self::Puducherry => 'Puducherry',
            self::AndamanAndNicobarIslands => 'Andaman and Nicobar Islands',
            self::Telangana => 'Telangana',
            self::AndhraPradesh => 'Andhra Pradesh',
            self::Ladakh => 'Ladakh',
            self::OtherTerritory => 'Other Territory',
        };
    }

    /**
     * Human-readable "Name (code)" used on documents.
     */
    public function displayName(): string
    {
        return "{$this->label()} ({$this->value})";
    }

    /**
     * Dropdown options keyed by GST code, sorted alphabetically by name.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->sortBy(fn (self $state) => $state->label())
            ->mapWithKeys(fn (self $state) => [$state->value => $state->displayName()])
            ->toArray();
    }
}
