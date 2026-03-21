<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GoogleContact;

class CompanyMatchingService
{
    /**
     * Attempts to link orphaned companies (no google_contact_id) 
     * to existing Google Contacts based on phone numbers or names.
     */
    public function runMatchingEngine()
    {
        // Use Eloquent instead of DB facade to avoid stdClass errors
        $orphanedCompanies = Company::whereNull('google_contact_id')->get();
        $contacts = GoogleContact::all();
        $matchedCount = 0;

        foreach ($orphanedCompanies as $company) {
            $matchFound = false;

            // 1. Try matching by Phone (Highest priority)
            if (!empty($company->mobile)) {
                $cleanCompanyPhone = $this->cleanPhoneNumber($company->mobile);
                
                foreach ($contacts as $contact) {
                    if (empty($contact->phone_number)) continue;
                    
                    $cleanContactPhone = $this->cleanPhoneNumber($contact->phone_number);
                    
                    if ($this->phonesMatch($cleanCompanyPhone, $cleanContactPhone)) {
                        $company->google_contact_id = $contact->id;
                        $company->save();
                        $matchFound = true;
                        $matchedCount++;
                        break; // Move to next company
                    }
                }
            }

            // 2. Try matching by Name (Fallback)
            if (!$matchFound && (!empty($company->business_name) || !empty($company->trade_name))) {
                foreach ($contacts as $contact) {
                    if ($this->namesMatch($company, $contact)) {
                        $company->google_contact_id = $contact->id;
                        $company->save();
                        $matchFound = true;
                        $matchedCount++;
                        break;
                    }
                }
            }
        }

        return $matchedCount;
    }

    private function cleanPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Sometimes Ecuadorian numbers have 593 prefix in Google Contacts
        if (str_starts_with($cleaned, '593')) {
            $cleaned = '0' . substr($cleaned, 3);
        }
        
        return $cleaned;
    }

    private function phonesMatch($phoneA, $phoneB)
    {
        // Require at least 7 digits to consider a match valid to avoid false positives
        if (strlen($phoneA) < 7 || strlen($phoneB) < 7) {
            return false;
        }

        return $phoneA === $phoneB || str_ends_with($phoneB, $phoneA) || str_ends_with($phoneA, $phoneB);
    }

    private function namesMatch(Company $company, GoogleContact $contact)
    {
        if (empty($contact->name)) return false;

        $contactName = strtolower(trim($contact->name));
        $businessName = strtolower(trim($company->business_name));
        $tradeName = strtolower(trim($company->trade_name ?? ''));

        // Direct exact match
        if ($contactName === $businessName || ($tradeName && $contactName === $tradeName)) {
            return true;
        }

        // Partial Match (e.g. Contact "Tienda Patty" matches Trade Name "Tienda Patty")
        if ($tradeName && str_contains($contactName, $tradeName)) {
            return true;
        }

        return false;
    }
}
