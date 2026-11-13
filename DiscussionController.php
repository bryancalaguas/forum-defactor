<?php
declare(strict_types=1);


namespace App\Controllers;


use App\Models\Comment;
use App\Models\Discussion;
use App\View;


final class DiscussionController
{
    public const INDENT_CAP      = 8;    // visual indentation stops growing here
    public const MAX_DEPTH_WALK  = 100;  // traversal limit (recursion guard)
    public const MAX_NODES       = 2000; // never render more than this many comments


    private Discussion $discussions;
    private Comment $comments;


    public function __construct()
    {
        $this->discussions = new Discussion();
        $this->comments    = new Comment();
    }


    /** GET / — list discussions. */
    public function index(): void
    {
        $list = array_map(fn (array $d) => $d + self::time($d['created_at']), $this->discussions->all());
        View::render('home', ['title' => 'Discussions', 'discussions' => $list]);
    }


    /** GET /discussions/{id} — one discussion with its reply tree. */
    public function show(string $id): void
    {
        // Malformed ID (e.g. "abc", "0", "-1", "1.5") → 400 Bad Request
        if (!ctype_digit($id) || (int) $id < 1) {
            View::error(400, 'The discussion ID must be a positive whole number.');
            return;
        }


        $discussion = $this->discussions->find((int) $id);
        if ($discussion === null) {
            View::error(404, 'Discussion not found.');
            return;
        }


        // 1) Group comments by parent: [parent_id => [child, child, ...]], 0 = top level
        $byParent = [];
        foreach ($this->comments->forDiscussion((int) $id) as $row) {
            $byParent[$row['parent_id'] ?? 0][] = $row;
        }


        // 2) Walk the tree depth-first and produce a flat list with depth info
        $flat = [];
        $this->flatten($byParent, 0, 0, [], $flat);


        View::render('discussion', [
            'title'         => $discussion['title'],
            'discussion'    => $discussion + self::time($discussion['created_at']),
            'comments'      => $flat,
            'comment_count' => count($flat),
        ]);
    }


    /**
     * Depth-first traversal. $path holds the ancestors of the current branch,
     * so a bad parent link (cycle) cannot cause infinite recursion.
     */
    private function flatten(array $byParent, int $parentId, int $depth, array $path, array &$out): void
    {
        if ($depth > self::MAX_DEPTH_WALK) {
            return;
        }
        foreach ($byParent[$parentId] ?? [] as $c) {
            $cid = (int) $c['id'];
            if (isset($path[$cid]) || count($out) >= self::MAX_NODES) {
                continue;
            }
            $out[] = [
                'id'           => $cid,
                'author'       => $c['author'],
                'avatar'       => '/img/default-avatar.svg',
                'body'         => $c['body'],
                'depth'        => $depth,                           // true depth label
                'indent'       => min($depth, self::INDENT_CAP),    // capped indentation
                'has_children' => !empty($byParent[$cid]),
            ] + self::time($c['created_at']);


            $this->flatten($byParent, $cid, $depth + 1, $path + [$cid => true], $out);
        }
    }


    /** Timestamps come from MySQL already in UTC (see Database.php). */
    private static function time(string $mysqlUtc): array
    {
        $dt = new \DateTimeImmutable($mysqlUtc, new \DateTimeZone('UTC'));
        return [
            'created_iso' => $dt->format('Y-m-d\TH:i:s\Z'),
            'created_utc' => $dt->format('M j, Y H:i') . ' UTC',
        ];
    }
}