<?php

namespace Fuzzybuilder\Lingvar\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Fuzzybuilder\Lingvar\Models\LingVar;
use \Illuminate\Support\Str;
use Exception;

class LingVarMacroProvider
{
    /**
     * Apply macros on the Builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function __construct()
    {
        $this->registerMacros();
    }

    /**
     * Register macros
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    private function registerMacros()
    {   
        Builder::macro('whereFuzzy', function ($name, $term, $minDegree = 0, $negation = null, $sort = false)
        {
            if (is_null($name) or is_null($term) or empty($name) or empty($term)) {
                throw new Exception("Argument is empty.");
            }

            $lingVar = new LingVar($name, $term, $minDegree, $sort, $negation);
            $collection = collect($this->get());
            $collection = $collection->map(function($state) use ($lingVar, $name){
                $state['fuzzyLingVarDegree'] = $lingVar->getFuzzyDegreeValue($state[$name]);
                return $state;
            });

            $collection = $collection->where('fuzzyLingVarDegree', '>=' , $minDegree)->flatten(2);
            return $collection;
        });

        /*Collection::macro('whereFuzzy', function ($name, $term, $minDegree = 0, $sort = null, $negation = false)
        {
            if (is_null($name) or is_null($term) or empty($name) or empty($term)) {
                throw new Exception("Argument is empty.");
            }

            $lingVar = new LingVar($name, $term, $minDegree, $sort, $negation);
            $collection = $this;
            $collection = $collection->map(function($state) use ($lingVar, $name){
                $state['fuzzyLingVarDegree'] = $lingVar->getFuzzyDegreeValue($state[$name]);
                return $state;
            });

            $collection = $collection->where('fuzzyLingVarDegree', '>=' , $minDegree)->flatten(2);
            return $collection;
        });*/

        Builder::macro('whereFuzzyNot', function ($name, $term, $minDegree = null, $sort = null)
        {
            return $this->whereFuzzy($name, $term, $minDegree, true, $sort);
        });


        Builder::macro('whereFuzzyMany', function ($colNameTerm, $operator, $minDegree = 0, $colIsNot = null)
        {
            $collection =  collect($this->get());
            $collection = $collection->map(function($state) use($colNameTerm, $operator, $colIsNot) {
                $degreeCollection = collect();
                $i = 0;
                foreach ($colNameTerm as $key => $value) {
                    $lingVar = new LingVar($key, $value, 0, null, $colIsNot == null ? 0 : $colIsNot[$i]);
                    $degreeCollection = $degreeCollection->push($lingVar->getFuzzyDegreeValue($state[$key]));
                    $i++;
                }
                if(Str::is($operator, 'and')) {
                    $state['fuzzyLingVarDegree'] = $degreeCollection->min();
                }
                else if (Str::is($operator, 'or')) {
                    $state['fuzzyLingVarDegree'] = $degreeCollection->max();
                }
                return $state;
            });
            $collection = $collection->where('fuzzyLingVarDegree', '>=' , $minDegree)->flatten(2);
            return $collection;
            
        });

        Collection::macro('getFuzzy', function ()
        {
            return $this->flatten(2);
        });
        
    }
}