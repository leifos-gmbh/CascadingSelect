<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\Service;

use ilAccess;

class Access
{
    public function __construct(
        protected ilAccess $access
    ) {
    }

    public function canCurrentUserChangeProfileSettings(): bool
    {
        return $this->access->checkAccess('write', '', USER_FOLDER_ID);
    }
}
