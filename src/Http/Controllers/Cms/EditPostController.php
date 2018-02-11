<?php

namespace Niku\Cms\Http\Controllers\Cms;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Niku\Cms\Http\Controllers\CmsController;

class EditPostController extends CmsController
{
    public function init(Request $request, $postType, $id)
    {
        $postTypeModel = $this->getPostType($postType);
    	if(!$postTypeModel){
    		$errorMessages = 'You are not authorized to do this.';
    		if(array_has($postTypeModel->errorMessages, 'post_type_does_not_exist')){
    			$errorMessages = $postTypeModel->errorMessages['post_type_does_not_exist'];
    		}
    		return $this->abort($errorMessages);
    	}

    	// Check if the post type has a identifier
    	if(empty($postTypeModel->identifier)){
    		$errorMessages = 'The post type does not have a identifier.';
    		if(array_has($postTypeModel->errorMessages, 'post_type_identifier_does_not_exist')){
    			$errorMessages = $postTypeModel->errorMessages['post_type_identifier_does_not_exist'];
    		}
    		return $this->abort($errorMessages);
		}

		// Validate if we need to validate a other post type before showing this post type
		$validateBefore = $this->validatePostTypeBefore($request, $postTypeModel, $id);
		if($validateBefore['status'] === false){
			$errorMessages = $validateBefore['message'];
    		return $this->abort($errorMessages, $validateBefore['config']);
		}
		
		// Disable editting of form
		if($postTypeModel->disableEditOnlyCheck){
        	$errorMessages = 'The post type does not support editting.';
    		if(array_has($postTypeModel->errorMessages, 'post_type_identifier_does_not_support_edit')){
    			$errorMessages = $postTypeModel->errorMessages['post_type_identifier_does_not_support_edit'];
    		}
    		return $this->abort($errorMessages);
		}
		

    	// Receive the post meta values
        $postmeta = $request->all();

        // Validating the request
        $validationRules = $this->validatePostFields($request->all(), $request, $postTypeModel);

        // Unset unrequired post meta keys
        $postmeta = $this->removeUnrequiredMetas($postmeta, $postTypeModel);

        // Get the post instance
        $post = $this->findPostInstance($postTypeModel, $request, $postType, $id);
        if(!$post){
        	$errorMessages = 'Post does not exist.';
    		if(array_has($postTypeModel->errorMessages, 'post_does_not_exist')){
    			$errorMessages = $postTypeModel->errorMessages['post_does_not_exist'];
    		}
    		return $this->abort($errorMessages);
		}

		$this->validatePost($request, $post, $validationRules);

		// Manipulate the request so we can empty out the values where the conditional field is not shown
		$postmeta = $this->removeValuesByConditionalLogic($postmeta, $postTypeModel, $post);

		// Saving the post values to the database
    	$post = $this->savePostToDatabase('edit', $post, $postTypeModel, $request, $postType);

        // Saving the post meta values to the database
        $this->savePostMetaToDatabase($postmeta, $postTypeModel, $post);

        // Lets fire events as registered in the post type
        $this->triggerEvent('on_edit', $postTypeModel, $post->id);

        $successMessage = 'Post succesfully updated.';
		if(array_has($postTypeModel->successMessage, 'post_updated')){
			$successMessage = $postTypeModel->successMessage['post_updated'];
		}

        // Lets return the response
    	return response()->json([
    		'code' => 'success',
    		'message' => $successMessage,
    		'action' => 'edit',
    		'post' => [
    			'id' => $post->id,
    			'post_title' => $post->post_title,
    			'post_name' => $post->post_name,
				'status' => $post->status,
				'post_type' => $post->post_type,
				'created_at' => $post->created_at,
				'updated_at' => $post->updated_at,
    		],
    	], 200);
    }

    /**
     * Validating the creation and change of a post
     */
    protected function validatePost($request, $post, $validationRules)
    {
    	$validationRules = $this->validateFieldByConditionalLogic($validationRules, $post, $post);

    	// Lets receive the current items from the post type validation array
    	if(array_key_exists('post_name', $validationRules) && !is_array($validationRules['post_name'])){

	    	$exploded = explode('|', $validationRules['post_name']);

	    	$validationRules['post_name'] = [];

	    	foreach($exploded as $key => $value){
	    		$validationRules['post_name'][] = $value;
	    	}
		}

    	// Lets validate if a post_name is required.
        if(!$post->disableDefaultPostName){

			// If we are edditing the current existing post, we must remove the unique check
			if($request->get('post_name') == $post->post_name){

		    	$validationRules['post_name'] = 'required';

		    // If this is not a existing post name, we need to validate if its unique. They are changing the post name.
		    } else {

		    	// Make sure that only the post_name of the requested post_type is unique
		        $validationRules['post_name'][] = 'required';
		        $validationRules['post_name'][] = Rule::unique('cms_posts')->where(function ($query) use ($post) {
				    return $query->where('post_type', $post->identifier);
				});

		    }

		}

        return $this->validate($request, $validationRules);
    }

}