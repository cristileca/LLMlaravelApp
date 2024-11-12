<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;

class DateService
{
    protected array $acceptedFormats = [
        'd.m.Y', 'm.d.Y', 'Y-m-d', 'd.n.Y', 'n.d.Y', 'j.m.Y', 'j.n.Y', 'm.j.Y', 'n.j.Y', 'd/m/Y', 'm/d/Y', 'd/n/Y', 'n/d/Y'
    ];

    public function __construct(array $acceptedFormats = null)
    {
        if($acceptedFormats) $this->acceptedFormats = $acceptedFormats;
    }

    public function parseDateWithMultipleFormats(string $dateString): Carbon|null
    {
        foreach ($this->acceptedFormats as $accepted_format) {
            try {
                $date = Carbon::createFromFormat($accepted_format, $dateString);
            } catch (\Exception $e) {
                continue;
            }

            if ($date && $date->format($accepted_format) === $dateString) {
                return $date;
            }
        }

        return null;
    }
}
