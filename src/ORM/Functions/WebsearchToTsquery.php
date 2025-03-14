<?php

declare(strict_types=1);

namespace App\ORM\Functions;

use MartinGeorgiev\Doctrine\ORM\Query\AST\Functions\BaseVariadicFunction;
use MartinGeorgiev\Doctrine\ORM\Query\AST\Functions\Exception\InvalidArgumentForVariadicFunctionException;

class WebsearchToTsquery extends BaseVariadicFunction
{
    protected function customizeFunction(): void
    {
        $this->setFunctionPrototype('websearch_to_tsquery(%s)');
    }

    protected function validateArguments(array $arguments): void
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1 || $argumentCount > 2) {
            throw InvalidArgumentForVariadicFunctionException::between('websearch_to_tsquery', 1, 2);
        }
    }
}
