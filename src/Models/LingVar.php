<?php

namespace Fuzzybuilder\Lingvar\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Exception;

class LingVar
{
    private $name;
    private $term;
    private $function;
    private $minDegree;
    private $sort;
    private $negation;

    public function __construct($name, $term, $minDegree, $sort, $negation) 
    {
        $this->name = $name;
        $this->term = $term;
        $this->minDegree =  is_null($minDegree) ? 0 : $minDegree; 
        $this->sort = $sort;
        $this->negation = $negation;
        $this->function = self::findFuzzyFunction($name, $term);
    }

    // Loads term definision file
    private function loadLingvarCollection()
    {
        try {
            $file = env('FUZZY_LINGVAR_PATH').env('FUZZY_LINGVAR_FILENAME');
            return new Collection(json_decode(file_get_contents($file), true));
        }
        catch (Exception $e) {
            throw new Exception("Can't load data from source file.");
        }
    }

    // Finding function for selected variable term
    private function findFuzzyFunction($name, $term)
    {
        $function = self::loadLingvarCollection()->where('name', $name)
        ->flatten(2)->where('term', $term)
        ->pluck('function')->get(0);

        if (is_null($function) or empty($function)) {
            throw new Exception("Can't find function atribute.");
        }

        return $function;
    }

    public function getFuzzyDegreeValue($x)
    {
        $result = 'return Fuzzybuilder\Lingvar\LingVarMath::'.$this->function.';';
        $result = str_replace('x', '$x', $result);
        if(!$this->negation)
        {
            return eval($result);
        }
        else
        {
            return 1-eval($result);
        }
    }

    // Ordering collection
    protected function fuzzyOrder($collection)
    {
        if (!is_null($this->sort)) {
            if($this->sort == 'asc') { 
                $collection = $collection->sortBy('degree');
            } elseif ($this->sort == 'desc') {
                $collection = $collection->sortByDesc('degree');
            } else {
                throw new Exception("Can't process sorting type.");
            }
        }
        
        return $collection;
    }

    protected function getName(){
        return $this->name;
    }

    protected function getTerm(){
        return $this->term;
    }

    protected function getMinDegree(){
        return $this->minDegree;
    }

    protected function getSort(){
        return $this->sort;
    }

    protected function getFunction(){
        return $this->function;
    }

    public function getNegation(){
        return $this->negation;
    }

    protected function setName($name){
        $this->name = $name;
    }

    protected function setTerm($term){
        $this->term = $term;
    }

    protected function setMinDegree($minDegree){
        $this->minDegree = $minDegree;
    }

    protected function setSort($sort){
        $this->sort = $sort;
    }

    protected function setFunction($function){
        $this->function = $function;
    }

    protected function setNegation($negation){
        $this->negation = $negation;
    }

}