<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect;

use ILIAS\User\Profile\Fields\Custom\Type as CustomUserFieldType;
use ILIAS\User\Context;
use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\UI\Component\Input\Field\Factory as FieldFactory;
use ILIAS\Language\Language as CoreLanguage;
use ILIAS\Refinery\Factory as Refinery;
use ilFormPropertyGUI;
use Leifos\CascadingSelect\Service\Container;
use Leifos\CascadingSelect\UI\FieldInputGUI;
use Leifos\CascadingSelect\DataObjects\ParserResult;
use ILIAS\Refinery\Transformation;

class UserFieldType implements CustomUserFieldType
{
    protected Container $container;

    public function container(): Container
    {
        return $this->container ??= new Container();
    }

    public function getLabel(CoreLanguage $lng): string
    {
        return $this->container()->language()->txt('cascading_type_name');
    }

    public function getAdditionalEditFormInputs(
        CoreLanguage $lng,
        FieldFactory $ff,
        Refinery $refinery,
        ?string $data
    ): ?FormInput {
        /* @var FormInput $input */
        $input = $ff->file(
            $this->container()->uploadHandler(),
            $this->container()->language()->txt('definition_values')
        )->withAcceptedMimeTypes(['text/xml'])->withMaxFiles(1);
        $input = $input->withAdditionalTransformation($refinery->custom()->transformation(
            function (array $value) use ($data): string|ParserResult {
                $from_input = (string) ($value[0] ?? '');
                $from_storage = (string) $data;
                /*
                 * The uploaded file is not kept around, so we have to make sure you
                 * can edit the field without reuploading the file every time.
                 */
                if ($from_input === '' && $from_storage !== '') {
                    return $from_storage;
                }
                $upload = $this->container()->uploadHandler()->getUploadAsStringAndCleanUp($from_input);
                return $this->container()->definitionParser()->definitionFromXML($upload);
            }
        ));
        $input = $input->withAdditionalTransformation($refinery->custom()->constraint(
            function (string|ParserResult $value): bool {
                return is_string($value) ? true : $value->isValid();
            },
            function (string|ParserResult $value): string {
                return is_string($value) ? '' : implode('<br/>', $value->errors());
            }
        ));
        $input = $input->withAdditionalTransformation($refinery->custom()->transformation(
            function (string|ParserResult $value) {
                return is_string($value) ? $value : $value->definition()->serialize();
            }
        ));
        return $input;
    }

    public function getLegacyInput(
        CoreLanguage $lng,
        Context $context,
        array $user_value,
        string $label,
        ?string $data
    ): ilFormPropertyGUI {
        $definition = $this->container()->definitionParser()->unserializeDefinition((string) $data);
        $input = new FieldInputGUI(
            $this->container()->access(),
            $this->container()->language(),
            $this->container()->templates(),
            $this->container()->inputParser(),
            $definition,
            $label
        );
        $input->setValue($user_value[0] ?? '');
        return $input;
    }

    public function prepareUserInputForStorage(mixed $input, ?string $data): ?array
    {
        return [$input];
    }
}
