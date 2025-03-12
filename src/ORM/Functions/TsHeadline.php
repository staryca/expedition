<?php

declare(strict_types=1);

namespace App\ORM\Functions;

use MartinGeorgiev\Doctrine\ORM\Query\AST\Functions\BaseVariadicFunction;

class TsHeadline extends BaseVariadicFunction
{
    protected function customiseFunction(): void
    {
        $this->setFunctionPrototype('ts_headline(%s)');
    }
}
