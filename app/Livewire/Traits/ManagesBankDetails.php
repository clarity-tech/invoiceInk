<?php

namespace App\Livewire\Traits;

use App\ValueObjects\BankDetails;

trait ManagesBankDetails
{
    public string $bank_account_name = '';

    public string $bank_account_number = '';

    public string $bank_name = '';

    public string $bank_ifsc = '';

    public string $bank_branch = '';

    public string $bank_swift = '';

    public string $bank_pan = '';

    /**
     * Populate bank detail properties from a BankDetails value object.
     */
    protected function fillBankDetails(?BankDetails $bankDetails): void
    {
        if (! $bankDetails) {
            return;
        }

        $this->bank_account_name = $bankDetails->accountName;
        $this->bank_account_number = $bankDetails->accountNumber;
        $this->bank_name = $bankDetails->bankName;
        $this->bank_ifsc = $bankDetails->ifsc;
        $this->bank_branch = $bankDetails->branch;
        $this->bank_swift = $bankDetails->swift;
        $this->bank_pan = $bankDetails->pan;
    }

    /**
     * Build a BankDetails value object from the current form properties.
     */
    protected function buildBankDetails(): BankDetails
    {
        return new BankDetails(
            accountName: $this->bank_account_name,
            accountNumber: $this->bank_account_number,
            bankName: $this->bank_name,
            ifsc: $this->bank_ifsc,
            branch: $this->bank_branch,
            swift: $this->bank_swift,
            pan: $this->bank_pan,
        );
    }

    /**
     * Reset all bank detail properties to empty strings.
     */
    protected function resetBankDetails(): void
    {
        $this->bank_account_name = '';
        $this->bank_account_number = '';
        $this->bank_name = '';
        $this->bank_ifsc = '';
        $this->bank_branch = '';
        $this->bank_swift = '';
        $this->bank_pan = '';
    }
}
