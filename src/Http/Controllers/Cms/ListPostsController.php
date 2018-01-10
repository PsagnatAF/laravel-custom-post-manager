<?php

namespace Niku\Cms\Http\Controllers\Cms;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Niku\Cms\Http\Controllers\CmsController;

class ListPostsController extends CmsController
{
	public function init(Request $request, $postType)
    {
    	// Lets validate if the post type exists and if so, continue.
    	$postTypeModel = $this->getPostType($postType);
    	if(!$postTypeModel){
    		return $this->abort('Custom post type does not exist');
    	}

    	// Check if the post type has a identifier
    	if(empty($postTypeModel->identifier)){
    		return $this->abort('The post type does not have a identifier.');
    	}

        // If the user can only see his own posts
        if($postTypeModel->userCanOnlySeeHisOwnPosts){
            $where[] = ['post_author', '=', Auth::user()->id];
        }

        // Lets check if we have configured a custom post type identifer
        if(!empty($postTypeModel->identifier)){
        	$postType = $postTypeModel->identifier;
        }

        // Where sql to get all posts by post_Type
        $where[] = ['post_type', '=', $postType];

        // Adding a custom query functionality so we can manipulate the find by the config
		if($postTypeModel->appendCustomWhereQueryToCmsPosts){
			foreach($postTypeModel->appendCustomWhereQueryToCmsPosts as $key => $value){
				$where[] = [$value[0], $value[1], $value[2]];
			}
		}

        // Query the database
		$posts = $postTypeModel::where($where)
			->select([
				'id',
				'post_title',
				'post_name',
				'status',
				'post_type',
			])
			->with('postmeta')
			->orderBy('id', 'desc')
			->get();

		// Lets fire events as registered in the post type
        $this->triggerEvent('on_browse', $postTypeModel, $posts);


		// Return the response
    	return response()->json([
			'label' => $postTypeModel->label,
			'objects' => $posts
		]);
    }
}
