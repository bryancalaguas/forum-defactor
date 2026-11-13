<?php
declare(strict_types=1);


namespace App\Controllers;


use App\Models\Comment;
use App\Models\Discussion;
use App\Session;
use App\View;


final class CommentController
{
    public const MAX_LENGTH = 2000;
    public const MAX_DEPTH  = 20;   // deepest displayed depth a new reply may have


    /** POST /discussions/{id}/comments — top-level comment or reply. */
    public function store(string $id): void
    {
        // 1) Must be logged in — checked on the server for EVERY write.
        $userId = Session::userId();
        if ($userId === null) {
            Session::flash('Please log in to post a reply.');
            View::redirect('/login');
        }


        // 2) CSRF token must match the one stored in the session.
        if (!Session::checkCsrf($_POST['csrf_token'] ?? null)) {
            View::error(403, 'Invalid or missing CSRF token. Reload the page and try again.');
            return;
        }


        // 3) Discussion must be valid and exist.
        if (!ctype_digit($id) || (int) $id < 1) {
            View::error(400, 'The discussion ID must be a positive whole number.');
            return;
        }
        $discussionId = (int) $id;
        if ((new Discussion())->find($discussionId) === null) {
            View::error(404, 'Discussion not found.');
            return;
        }


        // 4) Validate content.
        $body = trim((string) ($_POST['body'] ?? ''));
        $len  = mb_strlen($body);
        if ($len === 0 || $len > self::MAX_LENGTH) {
            View::error(422, 'A comment must contain 1 to ' . self::MAX_LENGTH . ' characters.');
            return;
        }


        // 5) Validate parent (optional). It must exist, be in the SAME discussion,
        //    and not exceed the depth limit. Parents are never edited after insert,
        //    so a new row can never create a cycle.
        $comments = new Comment();
        $parentId = null;
        $rawParent = (string) ($_POST['parent_id'] ?? '');
        if ($rawParent !== '') {
            if (!ctype_digit($rawParent)) {
                View::error(422, 'Invalid parent comment.');
                return;
            }
            $parent = $comments->find((int) $rawParent);
            if ($parent === null || (int) $parent['discussion_id'] !== $discussionId) {
                View::error(422, 'The parent comment does not belong to this discussion.');
                return;
            }
            $parentDepth = $comments->depthOf((int) $rawParent);
            if ($parentDepth === null || $parentDepth + 1 > self::MAX_DEPTH) {
                View::error(422, 'This thread is too deep to reply to.');
                return;
            }
            $parentId = (int) $rawParent;
        }


        // 6) Save using the SESSION user id (never a form field), then redirect.
        $newId = $comments->create($discussionId, $parentId, $userId, $body);
        View::redirect("/discussions/{$discussionId}#comment-{$newId}");
    }
}