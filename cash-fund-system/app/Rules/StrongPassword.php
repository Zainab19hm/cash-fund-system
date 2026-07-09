<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    private array $commonPasswords = [
        'password', '123456', '12345678', 'qwerty', 'abc123', 'monkey', 'master',
        'dragon', 'login', 'princess', 'football', 'shadow', 'sunshine', 'trustno1',
        'iloveyou', 'batman', 'access', 'hello', 'charlie', 'letmein', 'welcome',
        'password1', 'admin', 'passw0rd', 'p@ssword', 'pass123', '123456789',
        '1234567890', '123123', '000000', 'qwerty123', '1q2w3e4r', 'test',
        'guest', 'master123', 'admin123', 'root', 'toor', 'pass', '1234',
        '12345', '1234567', '12345678', '123456789', '1234567890',
    ];

    public function passes($attribute, $value): bool
    {
        if (strlen($value) < 12) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            return false;
        }

        if (!preg_match('/[a-z]/', $value)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $value)) {
            return false;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $value)) {
            return false;
        }

        if (in_array(strtolower($value), $this->commonPasswords)) {
            return false;
        }

        $name = strtolower(auth()->user()->name ?? '');
        $username = strtolower(auth()->user()->username ?? '');
        $valueLower = strtolower($value);

        if ($name && str_contains($valueLower, $name)) {
            return false;
        }

        if ($username && str_contains($valueLower, $username)) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return 'كلمة المرور يجب أن تكون 12 حرفاً على الأقل وتتضمن: حرف كبير (A-Z)، حرف صغير (a-z)، رقم (0-9)، ورمز خاص (!@#$%^&*...). كما يجب ألا تكون كلمة مرور شائعة.';
    }
}
