<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Carbon\Carbon;



class QueryFiler extends Model
{
    protected Request $request;
    protected Builder $query;
    protected array $relationsMap;
    protected array $globalSearchFields;
    protected string $defaultSortField;
    protected array $defaultParentFilterField;


    public function __construct(array $relationsMap = [], array $globalSearchFields = [], string $defaultSortField = 'id', array $defaultParentFilterField = [])
    {
        $this->relationsMap = $relationsMap;
        $this->globalSearchFields = $globalSearchFields;
        $this->defaultSortField = $defaultSortField;
        $this->defaultParentFilterField = $defaultParentFilterField;
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $this->query = $query;
        $this->request = $request;

        $this->applyGlobalFilter();
        $this->applyFieldFilters();
        $this->applyFindChildWithParent();
        $this->applySorting();

        return $this->query;
    }

    protected function applyGlobalFilter()
    {
        if ($this->request->filled('filters.global.value') && !empty($this->globalSearchFields)) {
            $value = $this->request->input('filters.global.value');

            $this->query->where(function ($q) use ($value) {
                foreach ($this->globalSearchFields as $field) {
                    if (str_contains($field, '.')) {
                        [$relation, $col] = explode('.', $field);
                        $q->orWhereHas($relation, function ($sub) use ($col, $value) {
                            $sub->where($col, 'like', "%$value%");
                        });
                    } else {
                        $q->orWhere($field, 'like', "%$value%");
                    }
                }
            });
        }
    }
    protected function applyFieldFilters()
    {
        foreach ($this->request->input('filters', []) as $field => $filter) {
            if ($field === 'global') continue;

            $operator = $filter['operator'] ?? 'and';
            $constraints = $filter['constraints'] ?? [];

            $this->query->where(function ($q) use ($field, $constraints, $operator) {
                foreach ($constraints as $rule) {
                    $value = $rule['value'] ?? null;
                    $mode = $rule['matchMode'] ?? 'contains';
                    if (is_null($value)) continue;

                    // Map field to relation if needed
                    [$relation, $column] = $this->getRelationAndColumn($field);

                    $clause = match ($mode) {
                        'startsWith' => [$column, 'like', $value . '%'],
                        'endsWith'   => [$column, 'like', '%' . $value],
                        'contains'   => [$column, 'like', '%' . $value . '%'],
                        'equals'     => [$column, '=', $value],
                        'notEquals'  => [$column, '!=', $value],
                        'in'         => [$column, $value],
                        'notIn'      => [$column, 'not in', $value], // ou gérer différemment

                        // Numeric match
                        'gt'         => [$column, '>', $value],
                        'lt'         => [$column, '<', $value],
                        'gte'        => [$column, '>=', $value],
                        'lte'        => [$column, '<=', $value],
                        //Date match
                        'dateAfter'         => [$column, '>', Carbon::parse(preg_replace('/GMT\+\d{4}.*$/', '', $value))->format('Y-m-d H:i:s')],
                        'dateBefore'         => [$column, '<', Carbon::parse(preg_replace('/GMT\+\d{4}.*$/', '', $value))->format('Y-m-d H:i:s')],
                        'dateIsNot'        => [$column, '<>', Carbon::parse(preg_replace('/GMT\+\d{4}.*$/', '', $value))->format('Y-m-d H:i:s')],
                        'dateIs'        => [$column, '=', Carbon::parse(preg_replace('/GMT\+\d{4}.*$/', '', $value))->format('Y-m-d H:i:s')],

                        // Range (between two values)
                        'between'    => [$column, 'between', $value], // ici `$value` doit être un tableau [min, max]

                        default      => null,
                    };

                    if (!$clause) continue;

                    if ($relation) {
                        $q->whereHas($relation, function ($sub) use ($clause, $operator) {
                            $operator === 'or'
                                ? $sub->orWhere(...$clause)
                                : $sub->where(...$clause);
                        });
                    } else if($field === 'created_at' OR $field === 'updated_at'){
                        $operator === 'or'
                            ? $q->orWhereDate(...$clause)
                            : $q->whereDate(...$clause);
                    } else{
                        $operator === 'or'
                            ? $q->orWhere(...$clause)
                            : $q->where(...$clause);
                    }
                }
            });
        }
    }
    protected function applyFindChildWithParent()
    {
        if (count($this->defaultParentFilterField) < 1) {
            return;
        } else {
            $this->query->where(function ($q) {
                foreach ($this->defaultParentFilterField as $field) {
                    if ($this->request->filled($field)) {
                        $q->where($field, $this->request->input($field));
                    }
                }
            });
        }
    }

    protected function applySorting()
    {
        if ($this->request->filled('sortField') && $this->request->filled('sortOrder')) {
            $direction = $this->request->sortOrder == -1 ? 'desc' : 'asc';
            $this->query->orderBy($this->request->sortField, $direction);
        } else {
            $this->query->orderByDesc($this->defaultSortField);
        }
    }

    protected function getRelationAndColumn(string $field): array
    {
        if (array_key_exists($field, $this->relationsMap)) {
            return explode('.', $this->relationsMap[$field]) + [null, null];
        }

        return [null, $field];
    }
}
