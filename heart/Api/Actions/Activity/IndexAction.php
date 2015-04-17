<?php namespace Qdiscuss\Api\Actions\Activity;

use Qdiscuss\Core\Repositories\UserRepositoryInterface;
use Qdiscuss\Core\Repositories\ActivityRepositoryInterface;
use Qdiscuss\Core\Actions\BaseAction;
use Qdiscuss\Api\Serializers\ActivitySerializer;

class IndexAction extends BaseAction
{
    protected $params;
    protected $actor;
    protected $users;
    protected $activity;
    
    /**
     * Instantiate the action.
     *
     * @param  \Qdiscuss\Search\Discussions\UserSearcher  $searcher
     */
    public function __construct(UserRepositoryInterface $user, ActivityRepositoryInterface $activity)
    {
        global $qdiscuss_actor, $qdiscuss_params;
        $this->actor = $qdiscuss_actor;
        $this->params = $qdiscuss_params;
        $this->users = $user;
        $this->activity = $activity;
    }

    /**
     * Show a user's activity feed.
     *
     * @return \Illuminate\Http\Response
     */
    public  function run()
    {
        $params = $this->params;
        $start = $params->start();
        $count = $params->count(20, 50);
        $type  = $params->get('type');
        $id    = $params->get('users');

        $user = $this->users->findOrFail($id, $this->actor->getUser());

        $activity = $this->activity->findByUser($user->id, $this->actor->getUser(), $count, $start, $type);

        
        $serializer = new ActivitySerializer(['sender', 'post', 'post.discussion', 'post.user', 'post.discussion.startUser', 'post.discussion.lastUser'], ['user']);
        $document = $this->document()->setData($serializer->collection($activity));

        echo $this->respondWithDocument($document);exit();
    }
}