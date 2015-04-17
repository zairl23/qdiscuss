<?php namespace Qdiscuss\Core\Search\Discussions;

use Qdiscuss\Core\Models\Discussion;
use Qdiscuss\Core\Search\SearcherInterface;
use Qdiscuss\Core\Search\GambitManager;
// use Qdiscuss\Core\Repositories\DiscussionRepositoryInterface;
// use Qdiscuss\Core\Repositories\PostRepositoryInterface;
use Qdiscuss\Core\Repositories\EloquentDiscussionRepository;
use Qdiscuss\Core\Repositories\EloquentPostRepository;

class DiscussionSearcher implements SearcherInterface
{
    public $query;

    protected $sortMap = [
        'lastPost' => ['last_time', 'desc'],
        'replies'  => ['comments_count', 'desc'],
        'created'  => ['start_time', 'asc']
    ];

    protected $defaultSort = 'lastPost';

    protected $relevantPosts = [];

    protected $gambits;

    protected $discussions;

    public function __construct(GambitManager $gambits, EloquentDiscussionRepository $discussions, EloquentPostRepository $posts)
    {
        $this->gambits = $gambits;
        $this->discussions = $discussions;
        $this->posts = $posts;
    }

    public function addRelevantPost($discussionId, $postId)
    {
        if (empty($this->relevantPosts[$discussionId])) {
            $this->relevantPosts[$discussionId] = [];
        }
        $this->relevantPosts[$discussionId][] = $postId;
    }

    public function setDefaultSort($defaultSort)
    {
        $this->defaultSort = $defaultSort;
    }

    public function query()
    {
        return $this->query;
    }

    public function search(DiscussionSearchCriteria $criteria, $count = null, $start = 0, $load = [])
    {
        $this->user = $criteria->user;

        $this->query = $this->discussions->query();
        // ->whereCan($criteria->user, 'view');//neychang??
        $this->gambits->apply($criteria->query, $this);

        $total = $this->query->count();

        $sort = $criteria->sort;
        if (empty($sort)) {
            $sort = $this->defaultSort;
        }
        if (is_array($sort)) {
            foreach ($sort as $id) {
                $this->query->orderByRaw('id != '.(int) $id);
            }
        } else {
            list($column, $order) = $this->sortMap[$sort];
            $this->query->orderBy($column, $criteria->order ?: $order);
        }

        if ($start > 0) {
            $this->query->skip($start);
        }
        if ($count > 0) {
            $this->query->take($count + 1);
        }

        $discussions = $this->query->get();

        if ($count > 0 && $areMoreResults = $discussions->count() > $count) {
            $discussions->pop();
        }

        if (in_array('relevantPosts', $load) && count($this->relevantPosts)) {
            $load = array_diff($load, ['relevantPosts']);
            $postIds = [];
            foreach ($this->relevantPosts as $id => $posts) {
                $postIds = array_merge($postIds, array_slice($posts, 0, 2));
            }
            $posts = $this->posts->findByIds($postIds, $this->user)->load('user');

            foreach ($discussions as $discussion) {
                $discussion->relevantPosts = $posts->filter(function ($post) use ($discussion) {
                    return $post->discussion_id == $discussion->id;
                })
                ->each(function ($post) {
                    $pos = strpos(strtolower($post->content), strtolower($this->fulltext));
                    // TODO: make clipping more intelligent (full words only)
                    $start = max(0, $pos - 50);
                    $post->content = ($start > 0 ? '...' : '').str_limit(substr($post->content, $start), 300);
                });
            }
        }

        Discussion::setStateUser($this->user);
        $discussions->load($load);

        return new DiscussionSearchResults($discussions, $areMoreResults, $total);
    }
}