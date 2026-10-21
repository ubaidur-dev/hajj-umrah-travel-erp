<?php

namespace App\Support;

/** Validation rules for Pakistani data. Keeps every form in the system consistent. */
class Pk
{
    public const PHONE = '/^03\d{2}-\d{7}$/';            // 0300-1234567 (11 digits, starts with 03)
    public const CNIC = '/^\d{5}-\d{7}-\d$/';            // 42101-1234567-1 (13 digits)
    public const PASSPORT = '/^[A-Z]{2}\d{7}$/';         // AB1234567 (2 letters + 7 digits)
    public const EMAIL = '/^[^@\s]+@[^@\s]+\.com$/i';    // must end with .com

    public static function phone(): array { return ['nullable', 'regex:'.self::PHONE]; }
    public static function cnic(): array { return ['nullable', 'regex:'.self::CNIC]; }
    public static function passport(): array { return ['nullable', 'regex:'.self::PASSPORT]; }
    public static function email(bool $required = false): array { return [$required ? 'required' : 'nullable', 'email', 'regex:'.self::EMAIL, 'max:120']; }

    public static function messages(): array
    {
        $phone = 'Enter a valid mobile number, like 0300-1234567.';

        return [
            'phone.regex' => $phone, 'whatsapp.regex' => $phone,
            'cnic.regex' => 'CNIC must have 13 digits, like 42101-1234567-1.',
            'passport_no.regex' => 'Passport number must be 2 letters followed by 7 digits, like AB1234567.',
            'email.regex' => 'Email must end with .com, for example name@gmail.com.',
            'email.email' => 'Enter a valid email address.',
        ];
    }
}
