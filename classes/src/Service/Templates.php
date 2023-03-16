<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\Service;

class Templates
{
    protected \ilCascadingSelectPlugin $plugin;

    public function __construct(\ilCascadingSelectPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function get(string $name): \ilTemplate
    {
        return $this->plugin->getTemplate($name, true, true);
    }
}
