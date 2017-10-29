<?php

namespace App\Cms\PostTypes;

use Niku\Cms\Http\NikuPosts;

class CategoryPosts extends NikuPosts
{
    // The label of the custom post type
    public $label = 'Post categories';

    // Users can only view their own posts when this is set to true
    public $userCanOnlySeeHisOwnPosts = false;       

    public $config = [

    ];

    // Setting up the template structure
    public $templates = [
        'default' => [

            'label' => 'Default',

            'customFields' => [

                'description' => [
                    'component' => 'niku-cms-editor-customfield',
                    'label' => 'Description',
                ],

                'posts' => [
                    'component' => 'niku-cms-category-posts-customfield',
                    'label' => 'Associated posts',                    
                    'config' => [
                        'sub_post_type' => 'posts',
                        'posts_edit_url_identifier' => 'Single',
                    ],
                ],

            ],
        ],
    ];

    /**
     * Determine if the user is authorized to make this request.
     * You can create some custom function here to manipulate
     * the functionalty on some certain custom actions.
     */
    public function authorized()
    {
        return true;
    }

}