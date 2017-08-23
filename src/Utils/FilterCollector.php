<?php declare(strict_types = 1);

namespace blitzik\Routing\Utils;

use blitzik\Router\Exceptions\ParameterFilterAlreadySet;
use Nette\SmartObject;

final class FilterCollector
{
    use SmartObject;

    /** @var array */
    private $filters = [];


    public function addFilter(string $filterName, array $affectedParameters): void
    {
        $affectedParameters = array_unique($affectedParameters);
        if (empty($affectedParameters)) {
            return;
        }

        foreach ($affectedParameters as $parameterName) {
            if (isset($this->filters[$parameterName])) {
                throw new ParameterFilterAlreadySet();
            }
            $this->filters[$parameterName] = $filterName;
        }
    }


    public function isEmpty(): bool
    {
        return empty($this->filters);
    }


    public function removeFilter(string $filterName): void
    {
        foreach ($this->filters as $parameterName => $fName) {
            if ($fName === $filterName) {
                unset($this->filters[$parameterName]);
            }
        }
    }


    public function removeFilterFromParameter(string $parameterName): void
    {
        unset($this->filters[$parameterName]);
    }


    public function getFilters(): array
    {
        return $this->filters;
    }

}