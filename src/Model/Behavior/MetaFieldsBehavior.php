<?php

namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Database\Expression\QueryExpression;

use function PHPSTORM_META\type;

class MetaFieldsBehavior extends Behavior
{
    protected $_defaultConfig = [
        'metaFieldsAssoc' => [
            'className' => 'MetaFields',
            'foreignKey' => 'parent_id',
            'bindingKey' => 'id',
            'dependent' => true,
            'cascadeCallbacks' => true,
            'saveStrategy' => 'append',
            'propertyName' => 'meta_fields',
        ],
        'modelAssoc' => [
            'foreignKey' => 'parent_id',
            'bindingKey' => 'id',
        ],
        'metaTemplateFieldCounter' => ['counter'],

        'implementedEvents' => [
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.beforeFind' => 'beforeFind',
            'Model.beforeSave' => 'beforeSave',
        ],
        'implementedMethods' => [
            'normalizeMetafields' => 'normalizeMetafields',
        ],
        'implementedFinders' => [
            'metafieldValue' => 'findMetafieldValue',
        ],
    ];

    private $aliasScope = null;

    public function initialize(array $config): void
    {
        $this->bindAssociations();
        $this->_metaTemplateFieldTable = $this->_table;
        $this->_metaTemplateTable = $this->_table;
    }

    public function getScope()
    {
        if (is_null($this->aliasScope)) {
            $this->aliasScope = Inflector::underscore(Inflector::singularize($this->_table->getAlias()));
        }
        return $this->aliasScope;
        
    }

    public function bindAssociations()
    {
        $config = $this->getConfig();
        $metaFieldsAssoc = $config['metaFieldsAssoc'];
        $modelAssoc = $config['modelAssoc'];

        $table = $this->_table;
        $tableAlias = $this->_table->getAlias();

        $assocConditions = [
            'MetaFields.scope' => $this->getScope()
        ];
        if (!$table->hasAssociation('MetaFields')) {
            $table->hasMany('MetaFields', array_merge(
                $metaFieldsAssoc,
                [
                    'conditions' => $assocConditions
                ]
            ));
        }

        if (!$table->MetaFields->hasAssociation($tableAlias)) {
            $table->MetaFields->belongsTo($tableAlias, array_merge(
                $modelAssoc,
                [
                    'className' => get_class($table),
                ]
            ));
        }
    }

    public function beforeMarshal($event, $data, $options)
    {
        $property = $this->getConfig('metaFieldsAssoc.propertyName');
        $options['accessibleFields'][$property] = true;
        $options['associated']['MetaFields']['accessibleFields']['id'] = true;

        if (isset($data[$property])) {
            if (!empty($data[$property])) {
                $data[$property] = $this->normalizeMetafields($data[$property]);
            }
        }
    }

    public function beforeSave($event, $entity, $options)
    {
        if (empty($entity->metaFields)) {
            return;
        }
    }

    public function normalizeMetafields($metaFields)
    {
        return $metaFields;
    }

    /**
     * Usage:
     *     $this->{$model}->find('metaFieldValue', [
     *         ['meta_template_id' => 1, 'field' => 'email', 'value' => '%@domain.test'],
     *         ['meta_template_id' => 1, 'field' => 'country_code', 'value' => '!LU'],
     *         ['meta_template_id' => 1, 'field' => 'time_zone', 'value' => 'UTC+2'],
     *     ])
     *     $this->{$model}->find('metaFieldValue', [
     *         'AND' => [
     *             ['meta_template_id' => 1, 'field' => 'email', 'value' => '%@domain.test'],
     *             'OR' => [
     *                 ['meta_template_id' => 1, 'field' => 'time_zone', 'value' => 'UTC+1'],
     *                 ['meta_template_id' => 1, 'field' => 'time_zone', 'value' => 'UTC+2'],
     *             ],
     *         ],
     *     ])
     */
    public function findMetafieldValue(Query $query, array $filters)
    {
        if (empty($filters)) {
            return $query;
        }
        $conjugatedFilters = $this->buildConjugatedFilters($filters);
        $conditions = $this->buildConjugatedQuerySnippet($conjugatedFilters);
        $query->where($conditions);
        return $query;
    }

    protected function buildConjugatedFilters(array $filters): array
    {
        $conjugatedFilters = [];
        foreach ($filters as $operator => $subFilters) {
            if (is_numeric($operator)) {
                $conjugatedFilters[] = $subFilters;
            } else {
                if (!empty($subFilters)) {
                    $conjugatedFilters[$operator] = $this->buildConjugatedFilters($subFilters);
                }
            }
        }
        return $conjugatedFilters;
    }

    protected function buildConjugatedQuerySnippet(array $conjugatedFilters, string $parentOperator='AND'): array
    {
        $conditions = [];
        if (empty($conjugatedFilters['AND']) && empty($conjugatedFilters['OR'])) {
            if (count(array_filter(array_keys($conjugatedFilters), 'is_string')) > 0) {
                $conditions = $this->buildComposedQuerySnippet([$conjugatedFilters]);
            } else {
                $conditions = $this->buildComposedQuerySnippet($conjugatedFilters, $parentOperator);
            }
        } else {
            foreach ($conjugatedFilters as $subOperator => $subFilter) {
                $conditions[$subOperator] = $this->buildConjugatedQuerySnippet($subFilter, $subOperator);
            }
        }
        return $conditions;
    }

    public function buildComposedQuerySnippet(array $filters, string $operator='AND'): array
    {
        $conditions = [];
        foreach ($filters as $filterOperator => $filter) {
            $subQuery = $this->buildQuerySnippet($filter, true);
            $modelAlias = $this->_table->getAlias();
            $conditions[$operator][] = [$modelAlias . '.id IN' => $subQuery];
        }
        return $conditions;
    }


    protected function getQueryExpressionForField(QueryExpression $exp, string $field, string $value)
    {
        if (substr($value, 0, 1) == '!') {
            $value = substr($value, 1);
            $exp->notEq($field, $value);
        } else if (strpos($value, '%') != false) {
            $exp->like($field, $value);
        } else {
            $exp->eq($field, $value);
        }
        return $exp;
    }

    protected function buildQuerySnippet(array $filter)
    {
        $whereClosure = function (QueryExpression $exp) use ($filter) {
            foreach ($filter as $column => $value) {
                $keyedColumn = 'MetaFields.' . $column;
                $this->getQueryExpressionForField($exp, $keyedColumn, $value);
            }
            return $exp;
        };

        $foreignKey = $this->getConfig('modelAssoc.foreignKey');
        $query = $this->_table->MetaFields->find()
            ->select('MetaFields.' . $foreignKey)
            ->where($whereClosure);
        return $query;
    }

}
