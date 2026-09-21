<?php

namespace MetaFieldsTypes;

use DateTime;
use MetaFieldsTypes\TextType;

class DateType extends TextType
{
    public const OPERATORS = ['=', '!='];
    public const TYPE = 'date';

    /**
     * Formats accepted for a stored date, tried in order.
     *
     * Meta-field values are strings and `date` fields have never been
     * validated, so shipped templates already hold whatever their source
     * supplied -- `first.json` populates `establishment` and `member-since`
     * from the FIRST directory. A partial date is meaningful for those: a team
     * established in 1998 may have no recorded month or day, so `Y` and `Y-m`
     * are accepted rather than forcing a fabricated precision.
     *
     * Day-first and month-first orderings are mutually ambiguous, so only the
     * unambiguous ISO-style orderings and the day-first European forms are
     * accepted. `03/04/2026` is read as 3 April.
     */
    public const FORMATS = [
        'Y-m-d',
        'Y-m-d H:i:s',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:s',
        'Y/m/d',
        'd-m-Y',
        'd/m/Y',
        'Y-m',
        'Y',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Validate the provided value against the expected type
     *
     * @param string $value
     * @return boolean
     */
    public function validate(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }
        foreach (self::FORMATS as $format) {
            // The `!` resets unparsed components to the epoch rather than
            // letting the current date leak into a partial value.
            $parsed = DateTime::createFromFormat('!' . $format, $value);
            if ($parsed === false) {
                continue;
            }
            $errors = DateTime::getLastErrors();
            // PHP < 8.2 returns an array of zero counts; 8.2+ returns false.
            if (is_array($errors) && (!empty($errors['warning_count']) || !empty($errors['error_count']))) {
                continue;
            }
            return true;
        }
        return false;
    }
}
