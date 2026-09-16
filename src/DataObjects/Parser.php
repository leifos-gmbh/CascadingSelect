<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\DataObjects;

use DateTimeImmutable;
use SimpleXMLElement;
use DomDocument;
use ilLogger;

class Parser
{
    public function __construct(
        protected ?ilLogger $logger
    ) {
    }

    public function definitionFromXML(string $xml): ParserResult
    {
        $xsd = __DIR__ . '/../../xml/cascading_select.xsd';
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        if (!$dom->schemaValidate($xsd)) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $this->logger?->warning($error->message);
                $errors[] = $error->message;
            }
            return new ParserResult(
                new Definition([], []),
                $errors
            );
        }

        $simple_xml = new SimpleXMLElement($xml);
        $columns = $this->columnsFromColspecXML($simple_xml->colspec);
        $options = [];
        foreach ($simple_xml->option as $option_xml) {
            $options[] = $this->optionFromXML($option_xml);
        }
        return new ParserResult(
            new Definition($columns, $options),
            []
        );
    }

    protected function optionFromXML(SimpleXMLElement $option_xml, int $depth = 0): Option
    {
        $child_options = [];
        if ($depth <= 20) {
            foreach ($option_xml->option as $child_option_xml) {
                $child_options[] = $this->optionFromXML($child_option_xml, $depth + 1);
            }
        }

        return new Option(
            (string) ($option_xml['name'] ?? ''),
            (bool) ($option_xml['deprecated'] ?? false),
            $this->datetimeFromString((string) ($option_xml['deprecatedSince'] ?? '')),
            $this->datetimeFromString((string) ($option_xml['deprecatedUntil'] ?? '')),
            $child_options
        );
    }

    /**
     * @return Column[]
     */
    protected function columnsFromColspecXML(SimpleXMLElement $col_spec_xml): array
    {
        $columns = [];
        foreach ($col_spec_xml->column as $column_xml) {
            $columns[] = new Column(
                (string) ($column_xml['name'] ?? ''),
                (string) ($column_xml['default'] ?? '')
            );
        }
        return $columns;
    }

    public function unserializeDefinition(string $serialized_definition): Definition
    {
        $raw_array = json_decode($serialized_definition, true);
        if ($raw_array === null) {
            return new Definition([], []);
        }
        $columns = $this->unserializeColumns($raw_array['columns'] ?? []);
        $options = $this->unserializeOptions($raw_array['options'] ?? []);
        return new Definition($columns, $options);
    }

    protected function unserializeColumns(array $raw_columns): array
    {
        $columns = [];
        foreach ($raw_columns as $raw_column) {
            $columns[] = new Column(
                (string) ($raw_column['name'] ?? ''),
                (string) ($raw_column['default'] ?? '')
            );
        }
        return $columns;
    }

    protected function unserializeOptions(array $raw_options, int $depth = 0): array
    {
        if ($depth > 20) {
            return [];
        }

        $options = [];
        foreach ($raw_options as $raw_option) {
            $options[] = new Option(
                (string) ($raw_option['name'] ?? ''),
                (bool) ($raw_option['deprecated'] ?? ''),
                $this->datetimeFromString((string) ($raw_option['deprecated_since'] ?? '')),
                $this->datetimeFromString((string) ($raw_option['deprecated_until'] ?? '')),
                $this->unserializeOptions($raw_option['child_options'] ?? [], $depth + 1)
            );
        }
        return $options;
    }

    protected function datetimeFromString(string $datetime_string): ?DateTimeImmutable
    {
        if ($datetime_string === '') {
            return null;
        }
        return new DateTimeImmutable($datetime_string);
    }
}
