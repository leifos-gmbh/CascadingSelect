<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\UI;

use ILIAS\UI\Component\Tree\TreeRecursion;
use ILIAS\UI\Component\Tree\Node\Factory as NodeFactory;
use ILIAS\UI\Component\Tree\Node\Node as Node;
use ILIAS\UI\Component\Tree\Node\Bylined as BylinedNode;
use Leifos\CascadingSelect\DataObjects\CascadingOptions;
use Leifos\CascadingSelect\DataObjects\Factory;
use Leifos\CascadingSelect\Service\Language;

class OptionsTreeRecursion implements TreeRecursion
{
    protected Factory $options_factory;
    protected Language $lng;

    public function __construct(
        Factory $options_factory,
        Language $lng
    ) {
        $this->options_factory = $options_factory;
        $this->lng = $lng;
    }

    public function getChildren($record, $environment = null): array
    {
        /** @var $record CascadingOptions */
        $children = [];
        foreach ($record->options($this->options_factory) as $option) {
            $children[] = $option;
        }
        return $children;
    }

    public function build(NodeFactory $factory, $record, $environment = null): Node
    {
        /** @var $record CascadingOptions */

        $deprecations = [];
        if ($record->deprecated()) {
            $deprecations[] = $this->lng->txt('cascading_deprecated');
        }
        if ($record->deprecatedSince()) {
            $deprecations[] = $this->lng->txtFill(
                'cascading_deprecated_since',
                $record->deprecatedSince()
            );
        }
        if ($record->deprecatedUntil()) {
            $deprecations[] = $this->lng->txtFill(
                'cascading_deprecated_until',
                $record->deprecatedUntil()
            );
        }

        return $factory->bylined(
            $record->name(),
            implode(', ', $deprecations)
        );
    }
}
