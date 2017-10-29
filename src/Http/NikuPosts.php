<?php

namespace Niku\Cms\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class NikuPosts extends Model
{
    protected $table = 'cms_posts';

    protected $attributes = array(
	  	'template' => 'default'
	);
    

	/**
	 * Has Many connection to the post meta table
	 */
    public function postmeta()
    {
        return $this->hasMany('Niku\Cms\Http\NikuPostmeta', 'post_id', 'id');
    }

    /**
     * Get posts of taxonomy
     */
    public function posts()
    {
    	return $this->belongsToMany('Niku\Cms\Http\NikuPosts', 'cms_taxonomy', 'taxonomy_post_id', 'post_id');
    }

    /**
     * Get taxonomies of post
     */
    public function taxonomies()
    {
    	return $this->belongsToMany('Niku\Cms\Http\NikuPosts', 'cms_taxonomy', 'post_id', 'taxonomy_post_id');
    }

    /**
     * Retrieve the meta value of a certain key
     */
    public function getMeta($key)
    {
    	$postmeta = $this->postmeta;
    	$postmeta = $postmeta->keyBy('meta_key');
		if(array_has($postmeta, $key . '.meta_value')){
			$returnValue = $postmeta[$key]['meta_value'];
			return $returnValue;
		}
    }
    
    public function saveMetas($metas)
    {
        foreach($metas as $key => $value){
         
            // Saving it to the database
            $object = [
                'meta_key' => $key,
                'meta_value' => $value,
            ];

            // Update or create the meta key of the post
            $this->postmeta()->updateOrCreate([
                'meta_key' => $key
            ], $object);
        }        
    }
}
