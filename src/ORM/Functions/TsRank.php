<?php

declare(strict_types=1);

namespace App\ORM\Functions;

use MartinGeorgiev\Doctrine\ORM\Query\AST\Functions\BaseVariadicFunction;

class TsRank extends BaseVariadicFunction
{
    protected function customiseFunction(): void
    {
        $this->setFunctionPrototype('ts_rank(%s)');
    }
}
