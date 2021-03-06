<?php namespace Qdiscuss\Extend;

use Qdiscuss\Application;
use Closure;

class Relationship implements ExtenderInterface
{
	protected $parent;
	protected $type;
	protected $name;
	protected $child;

	public function __construct($parent, $type, $name, $child = null)
	{
		$this->parent = $parent;
		$this->type = $type;
		$this->name = $name;
		$this->child = $child;
	}

	public function extend(Application $app)
	{
		$parent = $this->parent;
		$parent::addRelationship($this->name, function ($model) {
			if ($this->type instanceof Closure) {
				return call_user_func($this->type, $model);
			} elseif ($this->type === 'belongsTo') {
				return $model->belongsTo($this->child, null, null, $this->name);
			} else {
				// @todo
			}
		});
	}

}