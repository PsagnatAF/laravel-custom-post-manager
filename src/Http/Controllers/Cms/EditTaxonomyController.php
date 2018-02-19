<?php

namespace Niku\Cms\Http\Controllers\Cms;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Niku\Cms\Http\NikuTaxonomies;
use Illuminate\Support\Facades\Auth;
use Niku\Cms\Http\Controllers\CmsController;

class EditTaxonomyController extends CmsController
{
    public function init(Request $request, $postType, $id, $taxonomyId)
    {
        $postTypeModel = $this->getPostType($postType);
    	if(!$postTypeModel){
    		$errorMessages = 'You are not authorized to do this.';
    		return $this->abort($errorMessages);
        }

        // Validate the required fields
        $this->validate($request, [
            'id' => 'required',
            'taxonomy_post_id' => 'required',
            'custom' => 'required',
        ]);        
        
    	// Check if the post type has a identifier
    	if(empty($postTypeModel->identifier)){
    		$errorMessages = 'The post type does not have a identifier.';
    		if(array_has($postTypeModel->errorMessages, 'post_type_identifier_does_not_exist')){
    			$errorMessages = $postTypeModel->errorMessages['post_type_identifier_does_not_exist'];
    		}
    		return $this->abort($errorMessages);
		}
		
		// Disable editting of form
		if($postTypeModel->disableEditOnlyCheck){
        	$errorMessages = 'The post type does not support editting.';
    		if(array_has($postTypeModel->errorMessages, 'post_type_identifier_does_not_support_edit')){
    			$errorMessages = $postTypeModel->errorMessages['post_type_identifier_does_not_support_edit'];
    		}
    		return $this->abort($errorMessages);
		}

		// Disable editting of form
		if($postTypeModel->disableEdit){
        	$errorMessages = 'The post type does not support editting.';
    		if(array_has($postTypeModel->errorMessages, 'post_type_identifier_does_not_support_edit')){
    			$errorMessages = $postTypeModel->errorMessages['post_type_identifier_does_not_support_edit'];
    		}
    		return $this->abort($errorMessages);
        }

        $taxonomyInstance = NikuTaxonomies::where([
            ['id', '=', $taxonomyId],
            ['taxonomy_post_id', '=', $request->taxonomy_post_id],
            ['post_id', '=', $request->post_id],
        ])->first();

        if(!$taxonomyInstance){
        	$errorMessages = 'Taxonomy does not exist.';
    		if(array_has($postTypeModel->errorMessages, 'taxonomy_does_not_exist')){
    			$errorMessages = $postTypeModel->errorMessages['taxonomy_does_not_exist'];
    		}
    		return $this->abort($errorMessages);
        }
        
        if(is_array($request->custom)){
            $taxonomyInstance->custom = json_encode($request->custom);
        } else {
            $taxonomyInstance->custom = $request->custom;
        }

        $taxonomyInstance->save();
        
        // Lets fire events as registered in the post type
        // $this->triggerEvent('on_edit_taxonomy_event', $postTypeModel, $post->id, $postmeta);

        $successMessage = 'Taxonomy succesfully updated.';
		if(array_has($postTypeModel->successMessage, 'taxonomy_updated')){
			$successMessage = $postTypeModel->successMessage['taxonomy_updated'];
		}

        // Lets return the response
    	return response()->json([
    		'code' => 'success',
    		'message' => $successMessage,
    		'action' => 'taxonomy_edit',
    		'taxonomy' => [
    			'id' => $taxonomyInstance->id,
    			'post_id' => $taxonomyInstance->post_id,
    			'taxonomy_post_id' => $taxonomyInstance->taxonomy_post_id,
				'custom' => $taxonomyInstance->custom,
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