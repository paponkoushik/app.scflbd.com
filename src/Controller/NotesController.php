<?php

namespace SCFL\App\Controller;


use Illuminate\Database\Capsule\Manager;
use Previewtechs\PHPUtilities\UUID;
use Slim\Http\Request;
use Slim\Http\Response;

class NotesController extends AppController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addNotes(Request $request, Response $response)
    {
        //Loggedin user's detaild
        $loggedInUserID = $request->getAttribute("authUser");

        //Processing the post data
        $postData['uuid'] = UUID::v4();
        $postData['comments'] = $request->getParsedBodyParam('comments');
        $postData['related_with'] = $request->getQueryParam('related_with');
        $postData['related_with_id'] = $request->getQueryParam('related_with_id');
        $postData['posted_by'] = $loggedInUserID['id'];
        $postData['is_private'] = $request->getParsedBodyParam('is_private');

        //Insert note with postData
        $noteId = Manager::table("notes")->insertGetId($postData);

        //Redirect according to getQueryParam "from" data
        $from = $request->getQueryParam("from");

        if(!empty($noteId)){
            $this->getFlash()->addMessage("success", "Note has been added");
            return $response->withRedirect($from);
        }

        $this->getFlash()->addMessage("error", "Failed to add note. Please try again!");
        return $response->withRedirect($from);
    }

    public function deleteNotes(Request $request, Response $response)
    {
        $noteUUID = $request->getAttribute('noteUUID');

        if (empty($noteUUID)) {
            $this->getFlash()->addMessage('error', 'Invalid notes');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }
        if (!empty($noteUUID)) {
            $deleteNote = Manager::table('notes')
                ->where('uuid', $noteUUID)
                ->delete();

            if ($deleteNote) {
                $this->getFlash()->addMessage('success', 'Note has been deleted successfully');
                $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
        }

        $this->getFlash()->addMessage('error', 'Invalid note');
        return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
    }
}