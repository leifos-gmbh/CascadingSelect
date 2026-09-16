<?php

declare(strict_types=1);

use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\FileUpload\Handler\HandlerResult;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Location;
use Leifos\CascadingSelect\Service\Container;

// TODO check in future versions whether this can be namespaced
class leifosDefinitionUploadHandlerGUI implements UploadHandler, ilCtrlBaseClassInterface
{
    protected const string CMD_UPLOAD = 'handleUpload';

    protected Container $container;

    public function container(): Container
    {
        return $this->container ??= new Container();
    }

    public function executeCommand()
    {
        if (!$this->container()->access()->canCurrentUserChangeProfileSettings()) {
            return;
        }
        $cmd = $this->container()->ctrl()->getCmd();
        if ($cmd !== self::CMD_UPLOAD) {
            return;
        }
        $this->handleUpload();
    }

    public function handleUpload(): void
    {
        $status = HandlerResult::STATUS_FAILED;
        $new_identification = 'unknown';
        $message = 'file upload failed';

        $this->container()->upload()->process();
        $uploadResults = $this->container()->upload()->getResults();
        $result = end($uploadResults);

        if ($result instanceof UploadResult && $result->isOK() && $result->getSize()) {
            $new_identification = uniqid('cascading_select_');
            $move_success = $this->container()->upload()->moveOneFileTo(
                $result,
                $new_identification,
                Location::TEMPORARY
            );
            if ($move_success) {
                $status = HandlerResult::STATUS_OK;
                $message = 'Upload ok';
            }
        }
        $handler_result = new BasicHandlerResult(
            $this->getFileIdentifierParameterName(),
            $status,
            $new_identification,
            $message
        );
        $response = $this->container()->http()->response()->withBody(Streams::ofString(json_encode($handler_result)));
        $this->container()->http()->saveResponse($response);
        $this->container()->http()->sendResponse();
        $this->container()->http()->close();
    }

    public function getUploadAsStringAndCleanUp(string $upload_folder): string
    {
        if ($upload_folder === '' || !$this->container()->tempFilesystem()->hasDir($upload_folder)) {
            return '';
        }

        $file_content = '';
        $files = $this->container()->tempFilesystem()->listContents($upload_folder);
        if (count($files) === 1 && ($files[0] ?? null)?->isFile()) {
            $file_content = $this->container()->tempFilesystem()->read($files[0]->getPath());
        }

        $this->container()->tempFilesystem()->deleteDir($upload_folder);
        return $file_content;
    }

    public function getFileIdentifierParameterName(): string
    {
        return self::DEFAULT_FILE_ID_PARAMETER;
    }

    public function getUploadURL(): string
    {
        return $this->container()->ctrl()->getLinkTarget($this, self::CMD_UPLOAD);
    }

    public function getFileRemovalURL(): string
    {
        return '';
    }

    public function getExistingFileInfoURL(): string
    {
        return '';
    }

    public function getInfoForExistingFiles(array $file_ids): array
    {
        return [];
    }

    public function getInfoResult(string $identifier): ?FileInfoResult
    {
        return null;
    }

    public function supportsChunkedUploads(): bool
    {
        return false;
    }
}
