<?php

namespace SCFL\App\Controller;


use Illuminate\Database\Capsule\Manager;
use Previewtechs\PHPUtilities\UUID;
use Slim\Http\Request;
use Slim\Http\Response;

class DownloadController extends AppController
{

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     *
     * Download company attachment as wise company
     */
    public function downloadAttachment(Request $request, Response $response)
    {
        $attachmentUUID = $request->getAttribute('attachmentUUID');
        $attachment = Manager::table('attachments')->where('uuid', $attachmentUUID)->first();

        if (!empty($attachment)) {
            $filename = $attachment->file_location_path;
            $path = '../uploads/documents/'; // '/uplods/'
            $download_file =  $path.$filename;

            if(!empty($filename)){
                // Check file is exists on given path.
                if(file_exists($download_file))
                {
                    header('Content-Disposition: attachment; filename=' . $filename);
                    readfile($download_file);
                    exit;
                }
            }

            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));

        }

        return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
    }
}