<?php

namespace Niku\Cms\Http\Controllers\Cms;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Niku\Cms\Http\Controllers\CmsController;

class EditPostController extends CmsController
{
	/**
     * The manager of the database communication for adding and manipulating posts
     */
    public function init(Request $request, $postType, $id)
    {
        $postTypeModel = $this->getPostType($postType);
    	if(!$postTypeModel){
    		return $this->abort('You are not authorized to do this.');
    	}

    	// Check if the post type has a identifier
    	if(empty($postTypeModel->identifier)){
    		return $this->abort('The post type does not have a identifier.');
    	}

    	// Receive the post meta values
        $postmeta = $request->all();

        // Validating the request
        $validationRules = $this->validatePostFields($request->all(), $request, $postTypeModel);

        // Unset unrequired post meta keys
        $postmeta = $this->removeUnrequiredMetas($postmeta);

        // Get the post instance
        $post = $this->findPostInstance($postTypeModel, $request, $postType, $id);
        if(!$post){
			return $this->abort('Post does not exist.');
		}

		$this->validatePost($request, $post, $validationRules);

		// Saving the post values to the database
    	$post = $this->savePostToDatabase($post, $postTypeModel, $request, $postType);

        // Saving the post meta values to the database
        $this->savePostMetaToDatabase($postmeta, $postTypeModel, $post);

        // Lets fire events as registered in the post type
        $this->triggerEvent('on_edit', $postTypeModel, $post->id);

        // Lets return the response
    	return response()->json([
    		'code' => 'success',
    		'message' => 'Post succesfully editted',
    	], 200);
    }

    protected function findPostInstance($postTypeModel, $request, $postType, $id)
    {
    	// Validating the postname of the given ID to make sure it can be
        // updated and it is not overriding a other duplicated postname.
        // If the user can only see his own posts
        if($postTypeModel->userCanOnlySeeHisOwnPosts){
            $where[] = ['post_author', '=', Auth::user()->id];
        }

        // Lets check if we have configured a custom post type identifer
        if(!empty($postTypeModel->identifier)){
        	$postType = $postTypeModel->identifier;
        }

		$where[] = ['id', '=', $id];
		$where[] = ['post_type', '=', $postType];

		return $postTypeModel::where($where)->first();
    }

    /**
     * Validating the creation and change of a post
     */
    protected function validatePost($request, $post, $validationRules)
    {
		if($request->get('post_name') == $post->post_name){
	    	$validationRules['post_name'] = 'required';
	    } else {

	    	// Make sure that only the post_name of the requested post_type is unique
            $validationRules['post_name'] = [
            	'required',
            	Rule::unique('cms_posts')->where(function ($query) use ($postTypeModel) {
				    return $query->where('post_type', $postTypeModel->identifier);
				})
            ];

	    }

        return $this->validate($request, $validationRules);
    }
}
