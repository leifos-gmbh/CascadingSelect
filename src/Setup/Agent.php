<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\Setup;

use ILIAS\Setup\Agent\NullAgent;
use ILIAS\Setup\Config;
use ILIAS\Setup\Objective;
use ILIAS\Setup\Metrics\Storage as MetricsStorage;
use ILIAS\Setup\ObjectiveCollection;
use ilDatabaseUpdateStepsExecutedObjective;
use ilDatabaseUpdateStepsMetricsCollectedObjective;

class Agent extends NullAgent
{
    public function getUpdateObjective(?Config $config = null): Objective
    {
        return new ObjectiveCollection(
            'CascadingSelect',
            true,
            new ilDatabaseUpdateStepsExecutedObjective(new DBUpdateSteps11())
        );
    }

    public function getStatusObjective(MetricsStorage $storage): Objective
    {
        return new ObjectiveCollection(
            'CascadingSelect',
            true,
            new ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new DBUpdateSteps11())
        );
    }
}
