<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\Service;

use ILIAS\DI\Container as GlobalContainer;
use Leifos\CascadingSelect\DataObjects\Factory;
use Leifos\CascadingSelect\Settings;
use Leifos\CascadingSelect\UI\Presenter;
use Leifos\CascadingSelect\UI\OptionsTreeRecursion;

class Container
{
    protected GlobalContainer $dic;

    protected \ilCascadingSelectPlugin $plugin;
    protected \ilGlobalTemplateInterface $tpl;
    protected \ilLogger $logger;
    protected \ilRbacSystem $rbac_system;
    protected \ilObjUser $user;
    protected \ilDBInterface $db;

    protected Language $lng;
    protected Templates $templates;
    protected Factory $factory;
    protected Settings $settings;
    protected Presenter $presenter;

    public function __construct(\ilCascadingSelectPlugin $plugin)
    {
        global $DIC;

        $this->dic = $DIC;
        $this->plugin = $plugin;
    }

    public function mainTemplate(): \ilGlobalTemplateInterface
    {
        if (isset($this->tpl)) {
            return $this->tpl;
        }
        return $this->tpl = $this->dic->ui()->mainTemplate();
    }

    public function logger(): \ilLogger
    {
        if (isset($this->logger)) {
            return $this->logger;
        }
        return $this->logger = $this->dic->logger()->udfcs();
    }

    public function rbacSystem(): \ilRbacSystem
    {
        if (isset($this->rbac_system)) {
            return $this->rbac_system;
        }
        return $this->rbac_system = $this->dic->rbac()->system();
    }

    public function user(): \ilObjUser
    {
        if (isset($this->user)) {
            return $this->user;
        }
        return $this->user = $this->dic->user();
    }

    public function database(): \ilDBInterface
    {
        if (isset($this->db)) {
            return $this->db;
        }
        return $this->db = $this->dic->database();
    }

    public function language(): Language
    {
        if (isset($this->lng)) {
            return $this->lng;
        }
        return $this->lng = new Language($this->plugin);
    }

    public function templates(): Templates
    {
        if (isset($this->templates)) {
            return $this->templates;
        }
        return $this->templates = new Templates($this->plugin);
    }

    public function factory(): Factory
    {
        if (isset($this->factory)) {
            return $this->factory;
        }
        return $this->factory = new Factory();
    }

    public function settings(): Settings
    {
        if (isset($this->settings)) {
            return $this->settings;
        }
        return $this->settings = new Settings(
            $this->database(),
            $this->factory()
        );
    }

    public function presenter(): Presenter
    {
        if (isset($this->presenter)) {
            return $this->presenter;
        }
        return $this->presenter = new Presenter(
            $this->language(),
            $this->dic->ui()->factory(),
            $this->dic->ui()->renderer(),
            $this->factory(),
            new OptionsTreeRecursion($this->factory(), $this->language())
        );
    }
}
