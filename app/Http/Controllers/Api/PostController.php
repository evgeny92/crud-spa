<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $category_id = (int)$request->input('category_id');

        $sortField = request('sort_field', 'created_at');
        if (!in_array($sortField, ['title', 'post_text', 'created_at'])) {
            $sortField = 'created_at';
        }

        $sortDirection = request('sort_direction', 'desc');
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $filled = array_filter(request()->only([
            'title',
            'post_text',
            'created_at'
        ]));

        $posts = Post::when(count($filled) > 0, function ($query) use ($filled) {
            foreach ($filled as $column => $value) {
                $query->where($column, 'LIKE', '%' . $value . '%');
            }
        })->when(request('search', '') != '', function ($query) {
            $query->where(function ($q) {
                $q->where('title', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('post_text', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('created_at', 'LIKE', '%' . request('search') . '%');
            });
        })->when(request('category_id', '') != '', function ($q) {
            $q->where('category_id', request('category_id'));
        })->orderBy($sortField, $sortDirection)->paginate(5);

        /* $posts = Post::when($category_id && !empty($category_id), function ($q) {
             $q->where('category_id', request('category_id'));
         })
             ->orderBy($sortField, $sortDirection)
             ->paginate(5);*/

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request)
    {
        if ($request->hasFile('thumbnail')) {
            $filename = $request->thumbnail->getClientOriginalName();
            info($filename);
        }

        $post = Post::create($request->validated());

        return new PostResource($post);
    }

    public function show(Post $post)
    {
        return new PostResource($post);
    }

    public function update(Post $post, StorePostRequest $request)
    {
        /*if ($request->hasFile('thumbnail')) {
            $filename = $request->thumbnail->getClientOriginalName();
            info($filename);
        }*/

        $post->update($request->validated());

        return new PostResource($post);
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return response()->noContent();
    }
}
