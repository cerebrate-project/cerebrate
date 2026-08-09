<?php

namespace MetaFieldsTypes;

use Cake\Database\Expression\QueryExpression;
use MetaFieldsTypes\TextType;

class BooleanType extends TextType
{
    public const OPERATORS = ['=', '!='];
    public const TYPE = 'boolean';

    /**
     * Representations accepted as true and as false.
     *
     * Deliberately permissive. Meta-field values are strings, `boolean` fields
     * have never been validated, and shipped templates are populated from
     * external sources -- ENISA's inventory writes `is-approved`, for instance.
     * Rejecting anything a reasonable person would call a boolean would strand
     * existing data: getMetaFieldsConflictsUnderTemplate() re-validates on
     * migration, so a value that cannot pass here cannot be carried to a newer
     * version of its template.
     */
    public const TRUTHY = ['1', 'true', 'yes', 'on', 'y', 't'];
    public const FALSEY = ['0', 'false', 'no', 'off', 'n', 'f'];

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
        return !is_null($this->normalise($value));
    }

    /**
     * Reduce a stored representation to true, false, or null when unrecognised.
     *
     * @param string $value
     * @return boolean|null
     */
    public function normalise(string $value): ?bool
    {
        $needle = strtolower(trim($value));
        if (in_array($needle, self::TRUTHY, true)) {
            return true;
        }
        if (in_array($needle, self::FALSEY, true)) {
            return false;
        }
        return null;
    }

    /**
     * Match on meaning rather than on the stored spelling, so a search for `1`
     * also finds `true` and `yes`.
     */
    public function setQueryExpression(QueryExpression $exp, string $searchValue, \App\Model\Entity\MetaTemplateField $metaTemplateField): QueryExpression
    {
        $isNegation = substr($searchValue, 0, 1) === '!';
        if ($isNegation) {
            $searchValue = substr($searchValue, 1);
        }

        $wanted = $this->normalise($searchValue);
        if (is_null($wanted)) {
            // Not a boolean at all -- fall back to matching the literal string.
            $textHandler = new TextType();
            return $textHandler->setQueryExpression($exp, ($isNegation ? '!' : '') . $searchValue, $metaTemplateField);
        }

        $matching = $wanted ? self::TRUTHY : self::FALSEY;
        $field = 'MetaFields.value';
        if ($isNegation) {
            $exp->notIn($field, $matching);
        } else {
            $exp->in($field, $matching);
        }
        return $exp;
    }
}
