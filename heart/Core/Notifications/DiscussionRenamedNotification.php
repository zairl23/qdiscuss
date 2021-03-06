<?php namespace Qdiscuss\Core\Notifications;

use Qdiscuss\Core\Models\DiscussionRenamedPost;

class DiscussionRenamedNotification extends NotificationAbstract
{
    protected $post;

    public function __construct(DiscussionRenamedPost $post)
    {
        $this->post = $post;
    }

    public function getSubject()
    {
        return $this->post->discussion;
    }

    public function getSender()
    {
        return $this->post->user;
    }

    public function getData()
    {
        return ['postNumber' => (int) $this->post->number];
    }

    public static function getType()
    {
        return 'discussionRenamed';
    }

    public static function getSubjectModel()
    {
        return 'Qdiscuss\Core\Models\Discussion';
    }
}
