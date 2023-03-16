<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\UI;

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\UI\Component\Card\Card;
use Leifos\CascadingSelect\DataObjects\ColumnsDefinition;
use Leifos\CascadingSelect\DataObjects\CascadingOptions;
use Leifos\CascadingSelect\DataObjects\Factory;
use Leifos\CascadingSelect\Service\Language;

class Presenter
{
    protected Language $lng;
    protected UIFactory $ui_factory;
    protected UIRenderer $ui_renderer;
    protected Factory $factory;
    protected OptionsTreeRecursion $recursion;

    public function __construct(
        Language $lng,
        UIFactory $ui_factory,
        UIRenderer $ui_renderer,
        Factory $factory,
        OptionsTreeRecursion $recursion
    ) {
        $this->lng = $lng;
        $this->ui_factory = $ui_factory;
        $this->ui_renderer = $ui_renderer;
        $this->factory = $factory;
        $this->recursion = $recursion;
    }

    public function render(
        ColumnsDefinition $columns,
        CascadingOptions $options
    ): string {
        return $this->ui_renderer->render([
            $this->getColumnsCard($columns),
            $this->ui_factory->divider()->horizontal(),
            $this->getOptionsCard($options)
        ]);
    }

    protected function getColumnsCard(ColumnsDefinition $columns): Card
    {
        $items = [];
        foreach ($columns->defaults() as $name => $default) {
            if ($default) {
                $name .= ' (' . $this->lng->txt('cascading_default') . ': ' . $default . ')';
            }
            $items[] = $name;
        }

        return $this->ui_factory->card()->standard(
            $this->lng->txt('cascading_columns')
        )->withSections(
            [$this->ui_factory->listing()->unordered($items)]
        );
    }

    protected function getOptionsCard(CascadingOptions $options): Card
    {
        $top_level = [];
        foreach ($options->options($this->factory) as $option) {
            $top_level[] = $option;
        }
        $tree = $this->ui_factory->tree()->expandable(
            $this->lng->txt('cascading_options'),
            $this->recursion
        )->withData($top_level);

        return $this->ui_factory->card()->standard(
            $this->lng->txt('cascading_options')
        )->withSections([$tree]);
    }
}
