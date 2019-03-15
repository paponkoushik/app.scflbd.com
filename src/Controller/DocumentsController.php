<?php

namespace SCFL\App\Controller;


use Illuminate\Database\Capsule\Manager;
use League\Flysystem\FileExistsException;
use Previewtechs\PHPUtilities\UUID;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use SplFileInfo;

class DocumentsController extends AppController
{
    public function addAttachment(Request $request, Response $response)
    {
        /**
         * @var $files UploadedFile[]
         */
        $files = $request->getUploadedFiles();

        foreach ($files as $key => $value){
            $documents = [
                'uuid' => UUID::v4(),
                'related_with' => 'company',
                'related_with_id' => 1,
                'added_by' => $request->getAttribute("authUser")['id'],
                'file_type' => $value->getClientMediaType(),
                'size' => $value->getSize(),
                'name' => 'asdf',
                'extension' => end(explode(".", $value->getClientFilename())) ?: false
            ];

            $stream = fopen($value->file, 'r+');

            $fileName = $documents['uuid'];
            if(!empty($documents['extension'])){
                $fileName .= "." . $documents['extension'];
            }

            try {
                $result = $this->getDocumentUploader()->writeStream($fileName, $stream);
                fclose($stream);
            } catch (FileExistsException $e) {
                $this->getLogger()->error($e->getMessage());
                $this->getLogger()->debug($e->getTraceAsString());
            }

            $attachmentId = Manager::table("attachments")->insertGetId([
                'uuid' => UUID::v4(),
                'related_with' => $request->getQueryParam("related_with"),
                'related_with_id' => $request->getQueryParam("related_with_id"),
                'added_by' => $request->getAttribute("authUser")['id'],
                'file_location_path' => $fileName,
                'doc_type' => 'general',
                'file_type' => $value->getClientMediaType(),
                'name' => $request->getParsedBodyParam("name"),
                'is_private' => intval($request->getParsedBodyParam("is_private")) === 1 ? 1 : 0,
                'file_size' => $value->getSize()
            ]);

            if(!empty($attachmentId)){
                $this->getFlash()->addMessage("success", "Document has been added for this entity");
            }

            if(!empty($request->getServerParam("HTTP_REFERER"))){
                return $response->withRedirect($request->getServerParam("HTTP_REFERER"));
            }
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function deleteAttachment(Request $request, Response $response)
    {
        $attachmentUUID = $request->getAttribute('attachmentUUID');

        if (empty($attachmentUUID)) {
            $this->getFlash()->addMessage('error', 'Invalid Attachment');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }
        if (!empty($attachmentUUID)) {
            $deleteAttachment = Manager::table('attachments')
                ->where('uuid', $attachmentUUID)
                ->delete();

            if ($deleteAttachment) {
                $this->getFlash()->addMessage('success', 'Attachment has been deleted successfully');
                $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
        }

        $this->getFlash()->addMessage('error', 'Invalid Attachment');
        return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
    }
}