<?php

namespace MetaFieldsTypes;

use Cake\Database\Expression\QueryExpression;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

use MetaFieldsTypes\TextType;
use TypeError;

class IPv4Type extends TextType
{
    public const OPERATORS = ['contains', 'excludes'];
    public const TYPE = 'ipv4';

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
        return $this->_isValidIP($value) || $this->_isValidIP(explode('/', $value)[0]);
    }

    public function setQueryExpression(QueryExpression $exp, string $searchValue, \App\Model\Entity\MetaTemplateField $metaTemplateField): QueryExpression
    {
        if (strpos($searchValue, '%') !== false) {
            $textHandler = new TextType(); // we are wildcard filtering, use text filter instead
            return $textHandler->setQueryExpression($exp, $searchValue, $metaTemplateField);
        }
        $allMetaValues = $this->fetchAllValuesForThisType([], $metaTemplateField);
        $isNegation = false;
        if (substr($searchValue, 0, 1) == '!') {
            $searchValue = substr($searchValue, 1);
            $isNegation = true;
        }

        foreach ($allMetaValues as $fieldID => $ip) {
            if (!$this->IPInCidrBlock($searchValue, $ip)) {
                if (!$isNegation) {
                    unset($allMetaValues[$fieldID]);
                }
            } else if ($isNegation) {
                unset($allMetaValues[$fieldID]);
            }
        }
        $matchingIDs = array_keys($allMetaValues);
        if (!empty($matchingIDs)) {
            $exp->in('MetaFields.id', $matchingIDs);
        } else {
            $exp->eq('MetaFields.id', -1); // No matching meta-fields, generate an impossible condition to return nothing
        }
        return $exp;
    }

    protected function fetchAllMetatemplateFieldsIdForThisType(\App\Model\Entity\MetaTemplateField $metaTemplateField = null): Query
    {
        $conditions =[];
        if (!is_null($metaTemplateField)) {
            $conditions['id'] = $metaTemplateField->id;
        } else {
            $conditions['type'] = $this::TYPE;
        }
        $query = $this->MetaTemplateFields->find()->select(['id'])
            ->distinct()
            ->where($conditions);
        return $query;
    }

    protected function fetchAllValuesForThisType(array $conditions=[], \App\Model\Entity\MetaTemplateField $metaTemplateField=null): array
    {
        $metaTemplateFieldsIDs = $this->fetchAllMetatemplateFieldsIdForThisType($metaTemplateField);
        if (empty($metaTemplateFieldsIDs)) {
            return [];
        }
        $conditions = array_merge($conditions, ['meta_template_field_id IN' => $metaTemplateFieldsIDs]);
        $allMetaValues = $this->MetaFields->find('list', [
            'keyField' => 'id',
            'valueField' => 'value'
        ])->where($conditions)->toArray();
        return $allMetaValues;
    }

    /**
     * Convert a CIDR block to an array containing the minimum and maximum IP address for this block
     *
     * @param string $cidr an CIDR block with the form x.x.x.x/yy
     * @return array
     */
    protected function cidrToRange($cidr): array
    {
        $range = array();
        $cidr = explode('/', $cidr);
        if (count($cidr) == 1) { // No mask passed
            $cidr[1] = '32';
        }
        $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
        $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
        return $range;
    }

    /**
     * Check if the provided IP in contained by the CIDR block
     *
     * @param string $ip
     * @param string $cidr an CIDR block with the form x.x.x.x/yy
     * @return boolean
     */
    protected function _IPInCidrBlock(string $ip, string $cidr): bool
    {
        $range = $this->cidrToRange($cidr);
        return ip2long($range[0]) <= ip2long($ip) && ip2long($ip) <= ip2long($range[1]);
    }

    /**
     * Check if the provided cidr block in contained by the CIDR block
     *
     * @param string $cidr1 an CIDR block with the form x.x.x.x/yy
     * @param string $cidr2 an CIDR block with the form x.x.x.x/yy
     * @return boolean
     */
    protected function _cidrInCidrBlock(string $cidr1, string $cidr2): bool
    {
        $range = $this->cidrToRange($cidr1);
        return $this->_IPInCidrBlock($range[0], $cidr2) && $this->_IPInCidrBlock($range[1], $cidr2);
    }

    protected function _isValidIP(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    protected function _isValidCidrBlock(string $value): bool
    {
        $explodedValue = explode('/', $value);
        return $this->_isValidIP($explodedValue[0]);
    }

    protected function _isValidIPOrCidrBlock(string $value): bool
    {
        $explodedValue = explode('/', $value);
        if (count($explodedValue) == 1) {
            return $this->_isValidIP($value);
        } else if (count($explodedValue) == 2) {
            return $this->_isValidCidrBlock($value);
        }
        return false;
    }

    protected function IPInCidrBlock(string $ip, string $cidr, bool $throw=false): bool
    {
        if (!$this->_isValidCidrBlock($cidr)) {
            if ($throw) {
                throw new TypeError("Invalid CDIR block.");
            }
            return false;
        }
        if (!$this->_isValidIPOrCidrBlock($ip)) {
            if ($throw) {
                throw new TypeError("Invalid IP.");
            }
            return false;
        }

        $explodedIp = explode('/', $ip);
        if (count($explodedIp) == 1) {
            return $this->_IPInCidrBlock($ip, $cidr);
        } else {
            return $this->_cidrInCidrBlock($ip, $cidr);
        }
    }
}
