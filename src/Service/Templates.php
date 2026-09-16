<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\Service;

use ilTemplate;

class Templates
{
    public function __construct()
    {
    }

    public function get(string $name): ilTemplate
    {
        return new ilTemplate(
            $name,
            true,
            true,
            'components/Leifos/CascadingSelect'
        );
    }
}
