<?php

declare(strict_types=1);

namespace App\ORM\Functions;

use MartinGeorgiev\Doctrine\ORM\Query\AST\Functions\BaseVariadicFunction;

class WebsearchToTsquery extends BaseVariadicFunction
{
    protected function customiseFunction(): void
    {
        $this->setFunctionPrototype('websearch_to_tsquery(%s)');
    }
}
