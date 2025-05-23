<?php

namespace App\Classes;

class PasswordGenerator {
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const NUMBERS = '0123456789';
    private const SPECIAL = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    public function generate(
        int $length,
        int $lowercase = 0,
        int $uppercase = 0,
        int $numbers = 0,
        int $special = 0
    ): string {
        // Validate total length matches sum of character types
        $total = $lowercase + $uppercase + $numbers + $special;
        if ($total !== $length) {
            throw new \InvalidArgumentException('Sum of character types must equal total length');
        }

        $password = '';

        // Generate each type of character
        $password .= $this->getRandomChars(self::LOWERCASE, $lowercase);
        $password .= $this->getRandomChars(self::UPPERCASE, $uppercase);
        $password .= $this->getRandomChars(self::NUMBERS, $numbers);
        $password .= $this->getRandomChars(self::SPECIAL, $special);

        // Shuffle the password
        return str_shuffle($password);
    }

    public function generateWithPercentages(
        int $length,
        float $lowercasePercent = 0,
        float $uppercasePercent = 0,
        float $numbersPercent = 0,
        float $specialPercent = 0
    ): string {
        // Convert percentages to character counts
        $lowercase = (int) round(($lowercasePercent / 100) * $length);
        $uppercase = (int) round(($uppercasePercent / 100) * $length);
        $numbers = (int) round(($numbersPercent / 100) * $length);
        $special = (int) round(($specialPercent / 100) * $length);

        // Adjust for rounding errors
        $total = $lowercase + $uppercase + $numbers + $special;
        $diff = $length - $total;
        
        if ($diff !== 0) {
            // Add or subtract the difference from the largest category
            $max = max($lowercase, $uppercase, $numbers, $special);
            if ($max === $lowercase) $lowercase += $diff;
            elseif ($max === $uppercase) $uppercase += $diff;
            elseif ($max === $numbers) $numbers += $diff;
            else $special += $diff;
        }

        return $this->generate($length, $lowercase, $uppercase, $numbers, $special);
    }

    private function getRandomChars(string $characters, int $count): string {
        $result = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $count; $i++) {
            $result .= $characters[random_int(0, $max)];
        }
        return $result;
    }
} 