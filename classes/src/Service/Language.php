<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\Service;

class Language
{
    protected \ilCascadingSelectPlugin $plugin;

    public function __construct(\ilCascadingSelectPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function txt(string $key): string
    {
        return $this->plugin->txt($key);
    }

    public function txtFill(string $key, string ...$value): string
    {
        return sprintf($this->txt($key), ...$value);
    }
}
