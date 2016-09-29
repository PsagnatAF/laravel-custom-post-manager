<?php

namespace Niku\Cms\Http;

use Illuminate\Database\Eloquent\Model;

class Postmeta extends Model
{
    protected $table = 'cms_postmeta';
    protected $fillable = ['meta_key', 'meta_value'];

    public function post()
    {
    	return $this->hasOne('Niku\Cms\Http\Posts', 'post_id');
    }

}

