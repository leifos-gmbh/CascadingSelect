<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\Service;

use ILIAS\DI\Container as GlobalContainer;
use Leifos\CascadingSelect\DataObjects\Parser as DefinitionParser;
use Leifos\CascadingSelect\UI\InputParser;
use leifosDefinitionUploadHandlerGUI;
use ilLogger;
use ilCtrlInterface;
use ILIAS\FileUpload\FileUpload;
use ILIAS\HTTP\Services as HTTP;
use ILIAS\Filesystem\Filesystem;

class Container
{
    protected GlobalContainer $dic;

    protected Access $access;
    protected Language $language;
    protected Templates $templates;
    protected DefinitionParser $definition_parser;
    protected InputParser $input_parser;
    protected leifosDefinitionUploadHandlerGUI $upload_handler;

    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
    }

    protected function logger(): ilLogger
    {
        return $this->dic->logger()->udfcs();
    }

    public function ctrl(): ilCtrlInterface
    {
        return $this->dic->ctrl();
    }

    public function upload(): FileUpload
    {
        return $this->dic->upload();
    }

    public function http(): HTTP
    {
        return $this->dic->http();
    }

    public function tempFilesystem(): Filesystem
    {
        return $this->dic->filesystem()->temp();
    }

    public function access(): Access
    {
        return $this->access ??= new Access($this->dic->access());
    }

    public function language(): Language
    {
        return $this->language ??= new Language($this->dic->user());
    }

    public function templates(): Templates
    {
        return $this->templates ??= new Templates();
    }

    public function definitionParser(): DefinitionParser
    {
        return $this->definition_parser ??= new DefinitionParser($this->logger());
    }

    public function inputParser(): InputParser
    {
        return $this->input_parser ??= new InputParser();
    }

    public function uploadHandler(): leifosDefinitionUploadHandlerGUI
    {
        return $this->upload_handler ??= new leifosDefinitionUploadHandlerGUI();
    }
}
