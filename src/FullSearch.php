<?php

namespace EmilMoe\FullSearch;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use \Closure;

class FullSearch
{
    /**
     * Query for search results.
     *
     * @param null|string $keyword
     * @return array
     */
    public function search(?string $keyword = ''): array
    {
        $results = [];

        collect(config('full-search.include'))->each(function ($specification, $class) use ($keyword, &$results) {
            $builder = $class::select(array_merge($this->getColumns($specification['columns']), ['id']));

            if (isset($specification['except']) && is_array($specification['except'])) {
                $this->addExceptions($builder, $specification['except']);
            }

            if (isset($specification['filter']) && is_callable($specification['filter'])) {
                $this->addFilter($builder, $specification['filter']);
            }

            $builder->where(function ($builder) use ($specification, $keyword) {
                collect($specification['columns'])->each(function ($column) use ($keyword, &$builder) {
                    if (is_array($column)) {
                        $this->addConcatColumns($builder, $keyword, $column);
                    }

                    if (! is_array($column)) {
                        $this->addColumn($builder, $keyword, $column);
                    }
                });
            });

            $results[] = [
                'title'   => $specification['as'],
                'results' => $builder->limit(config('full-search.limit'))->get()->map(function ($result) use ($specification) {
                    $title = implode(' ', collect($specification['results']['title'])->map(function ($title) use ($result) {
                        if (is_callable($title)) {
                            return call_user_func($title, $result);
                        }
                        else {
                            return $result->$title;
                        }
                    })->toArray());

                    $info = implode(' ', collect($specification['results']['info'])->map(function ($info) use ($result) {
                        if (is_callable($info)) {
                            return call_user_func($info, $result);
                        }
                        else {
                            return $result->$info;
                        }
                    })->toArray());

                    return [
                        'id'    => $result->id,
                        'url'   => $this->generateUrl($specification['route'], $result->id, $result),
                        'title' => $title,
                        'info'  => $info,
                    ];
                })
            ];
        });

        return $results;
    }

    /**
     * Generate URL.
     *
     * @param  string|array $params
     * @param  int          $id
     * @return string
     */
    private function generateUrl($params, int $id, $result): string
    {
        if (is_callable($params)) {
            return call_user_func($params, $id, $result);
        }

        if (is_array($params)) {
            return route($params[0], array_merge([$id], $params[1]));
        }

        if (! is_array($params)) {
            return route($params, $id);
        }
    }

    /**
     * Custom filter to query.
     *
     * @param Builder &$builder
     * @param Closure $filter
     * @return void
     */
    private function addFilter(Builder &$builder, Closure $filter): void 
    {
        call_user_func($filter, $builder);
    }

    /**
     * Apply simple exceptions. More advanced must use a filter.
     *
     * @param Builder &$builder
     * @param array $exceptions
     * @return void
     */
    private function addExceptions(Builder &$builder, array $exceptions): void
    {
        collect($exceptions)->map(function ($exception) use (&$builder) {
            $builder->where($exception[0], '<>', $exception[1]);
        });
    }
    
    /**
     * Search for keyword across multiple columns.
     * 
     * @param Builder &$builder
     * @param string $keyword
     * @param array $columns 
     */
    private function addConcatColumns(Builder &$builder, string $keyword, array $columns): void
    {
        $concat = 'CONCAT(`'. implode('`, " ",`', $columns) .'`)';

        $builder->orWhere(DB::raw($concat), 'LIKE', '%'. $keyword .'%');

        collect($columns)->each(function ($column) use (&$builder) {
            $builder->orderBy($column, 'asc');
        });
    }

    /**
     * Search for keyword in single column.
     *
     * @param Builder &$builder
     * @param string $keyword
     * @param string $column 
     */
    private function addColumn(Builder &$builder, string $keyword, string $column): void
    {
        $builder->orWhere($column, 'LIKE', '%'. $keyword .'%')
            ->orderBy($column, 'asc');
    }

    /**
     * Get columns as flat array.
     *
     * @param array $columns
     * @return array
     */
    private function getColumns(array $columns): array
    {
        return collect($columns)->flatten()->toArray();
    }
}