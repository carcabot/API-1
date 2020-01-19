<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\IdentificationName;

class IdentificationHelper
{
    public function matchIdentifier(array $identifiers, string $value, string $name): bool
    {
        $now = new \DateTime();

        try {
            $identificationClass = new \ReflectionClass(IdentificationName::class);
            $name = $identificationClass->getConstant($name);
        } catch (\Exception $e) {
            throw $e;
        }

        foreach ($identifiers as $identifier) {
            if ($value === $identifier->getValue() && $name === $identifier->getName()->getValue()) {
                // validFrom null means it is valid from the beginning of time.
                if (null === $identifier->getValidFrom() || $now >= $identifier->getValidFrom()) {
                    // validThrough null means it is valid until the end of time.
                    if (null === $identifier->getValidThrough() || $now <= $identifier->getValidThrough()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function validateNRIC(string $nric)
    {
        if (9 !== \strlen($nric)) {
            return false;
        }

        $nric = \strtoupper($nric);
        $weight = 0;

        $weightMap = [
            1 => 2,
            2 => 7,
            3 => 6,
            4 => 5,
            5 => 4,
            6 => 3,
            7 => 2,
        ];

        foreach ($weightMap as $key => $weightMultiplier) {
            $weight += ((int) $nric[$key] * $weightMultiplier);
        }

        $offset = 0;
        if (\in_array($nric[0], ['T', 'G'], true)) {
            $offset = 4;
        }
        $alphaKey = ($offset + $weight) % 11;

        $stAlpha = ['J', 'Z', 'I', 'H', 'G', 'F', 'E', 'D', 'C', 'B', 'A'];
        $fgAlpha = ['X', 'W', 'U', 'T', 'R', 'Q', 'P', 'N', 'M', 'L', 'K'];

        $lastChar = null;
        if (\in_array($nric[0], ['S', 'T'], true)) {
            $lastChar = $stAlpha[$alphaKey];
        } elseif (\in_array($nric[0], ['F', 'G'], true)) {
            $lastChar = $fgAlpha[$alphaKey];
        }

        return $lastChar === $nric[8];
    }
}
