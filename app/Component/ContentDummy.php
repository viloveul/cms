<?php

namespace App\Component;

use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;

class ContentDummy
{
    /**
     * @var array
     */
    protected $postIds = [];

    /**
     * @var array
     */
    protected $tagIds = [];

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function posts()
    {
        $post = Post::updateOrCreate(['slug' => 'hello-world'], [
            'author_id' => $this->user->id,
            'title' => 'Hello World !!',
            'type' => 'post',
            'description' => 'Sample post content',
            'content' => '<p>It just sample content</p>',
            'status' => 1,
            'comment_enabled' => 1,
        ]);
        $post->tags()->sync($this->tagIds);
        $page = Post::updateOrCreate(['slug' => 'lorem-ipsum'], [
            'author_id' => $this->user->id,
            'title' => 'Lorem Ipsum',
            'type' => 'page',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Egestas sed tempus urna et pharetra pharetra massa massa. Vulputate odio ut enim blandit volutpat maecenas volutpat blandit aliquam. Nullam ac tortor vitae purus faucibus. Augu',
            'content' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Egestas sed tempus urna et pharetra pharetra massa massa. Vulputate odio ut enim blandit volutpat maecenas volutpat blandit aliquam. Nullam ac tortor vitae purus faucibus. Augue ut lectus arcu bibendum at varius vel pharetra vel. Rhoncus est pellentesque elit ullamcorper dignissim. At tempor commodo ullamcorper a. Ut lectus arcu bibendum at varius vel. In aliquam sem fringilla ut morbi tincidunt augue interdum velit. Nibh venenatis cras sed felis eget velit aliquet sagittis id. Nisl vel pretium lectus quam id leo in. Tortor pretium viverra suspendisse potenti nullam. Luctus venenatis lectus magna fringilla. Mollis nunc sed id semper. Metus dictum at tempor commodo ullamcorper a lacus vestibulum. Cum sociis natoque penatibus et magnis dis parturient montes nascetur. Dui vivamus arcu felis bibendum ut tristique et egestas quis. Eget mauris pharetra et ultrices neque ornare. Purus ut faucibus pulvinar elementum integer enim neque. Lectus magna fringilla urna porttitor rhoncus dolor.</p><p>Aliquet sagittis id consectetur purus ut faucibus pulvinar elementum integer. Cras tincidunt lobortis feugiat vivamus at augue. Sagittis id consectetur purus ut faucibus pulvinar elementum. Vestibulum sed arcu non odio euismod lacinia at. Tellus integer feugiat scelerisque varius morbi enim nunc. Pulvinar sapien et ligula ullamcorper malesuada. Sollicitudin ac orci phasellus egestas tellus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames. Duis at tellus at urna condimentum mattis pellentesque id nibh. Orci phasellus egestas tellus rutrum tellus pellentesque. Consequat semper viverra nam libero justo laoreet sit amet cursus. Pulvinar pellentesque habitant morbi tristique senectus et netus et malesuada. Sit amet purus gravida quis blandit turpis cursus in. Cursus risus at ultrices mi. Dui faucibus in ornare quam viverra orci sagittis. Bibendum neque egestas congue quisque egestas diam in. Mattis rhoncus urna neque viverra. Urna nunc id cursus metus aliquam. Amet purus gravida quis blandit turpis. Suspendisse potenti nullam ac tortor vitae.</p><p>Massa placerat duis ultricies lacus sed turpis tincidunt id. Et pharetra pharetra massa massa ultricies mi quis hendrerit. Eu lobortis elementum nibh tellus molestie nunc non. Ac felis donec et odio pellentesque. Nunc congue nisi vitae suscipit tellus mauris a diam. Fermentum iaculis eu non diam phasellus vestibulum. Phasellus vestibulum lorem sed risus ultricies tristique. Egestas pretium aenean pharetra magna ac. Ullamcorper dignissim cras tincidunt lobortis feugiat vivamus at. Consectetur libero id faucibus nisl tincidunt eget. Ornare massa eget egestas purus viverra. Non arcu risus quis varius quam quisque. Morbi tincidunt augue interdum velit euismod in pellentesque. Cursus turpis massa tincidunt dui ut ornare lectus sit. Ultrices vitae auctor eu augue ut lectus arcu.</p><p>Libero volutpat sed cras ornare arcu dui vivamus. Ac tincidunt vitae semper quis lectus. Tellus mauris a diam maecenas sed enim ut. Diam maecenas ultricies mi eget mauris pharetra et ultrices neque. Magna eget est lorem ipsum dolor sit amet consectetur adipiscing. Massa ultricies mi quis hendrerit dolor magna eget est lorem. Fames ac turpis egestas sed tempus urna et pharetra pharetra. Integer enim neque volutpat ac tincidunt vitae. Sed risus pretium quam vulputate dignissim suspendisse in est. Non tellus orci ac auctor. Risus nullam eget felis eget nunc lobortis mattis. Sit amet massa vitae tortor. Etiam dignissim diam quis enim lobortis scelerisque fermentum dui. Amet massa vitae tortor condimentum lacinia quis. Ultricies integer quis auctor elit. Suscipit tellus mauris a diam.</p><p>Tempor id eu nisl nunc mi ipsum faucibus. A diam maecenas sed enim ut sem. Aliquet risus feugiat in ante metus dictum at tempor. Malesuada bibendum arcu vitae elementum curabitur vitae nunc sed. Interdum velit euismod in pellentesque massa placerat duis ultricies. Tortor consequat id porta nibh venenatis cras sed felis. Eu nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper. Tellus in hac habitasse platea dictumst vestibulum rhoncus est pellentesque. Egestas congue quisque egestas diam in. Phasellus vestibulum lorem sed risus ultricies tristique nulla. Id semper risus in hendrerit gravida rutrum quisque non. Lacus viverra vitae congue eu consequat. Nunc pulvinar sapien et ligula. A iaculis at erat pellentesque. Pellentesque diam volutpat commodo sed egestas. At volutpat diam ut venenatis tellus in.</p>',
            'status' => 1,
            'comment_enabled' => 0,
        ]);
        $this->postIds = [$post->id, $page->id];
    }

    public function run()
    {
        $this->tags();
        $this->posts();
        $this->comments();
    }

    public function tags()
    {
        $tag = Tag::updateOrCreate(['slug' => 'no-category'], [
            'type' => 'category',
            'title' => 'No Category',
            'status' => 1,
            'author_id' => $this->user->id,
        ]);
        $this->tagIds[] = $tag->id;
    }
}
