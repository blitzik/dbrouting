<?php declare(strict_types = 1);

namespace blitzik\Routing\Utils;

use Nette\SmartObject;

final class ParametersCollector
{
    use SmartObject;

    /** @var array */
    private $parameters = [];


    public function addParameter(string $name, string $value): void
    {
        $this->parameters[$name] = $value;
    }


    public function isEmpty(): bool
    {
        return empty($this->parameters);
    }


    public function getParameters(): array
    {
        return $this->parameters;
    }
}