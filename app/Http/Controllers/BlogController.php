<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogLike;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::with(['category', 'user'])
            ->published()
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->paginate(12);

        $categories = BlogCategory::where('is_active', true)
            ->withCount('blogs')
            ->get();

        $featuredBlogs = Blog::published()
            ->where('is_featured', true)
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->take(3)
            ->get();

        return ReactPage::render('blogs.index', compact('blogs', 'categories', 'featuredBlogs'));
    }

    public function show(string $slug)
    {
        $blog = Blog::with(['category', 'user', 'comments' => function ($query) {
            $query->where('is_approved', true)->whereNull('parent_id')->with(['user', 'replies' => function ($q) {
                $q->where('is_approved', true)->with('user');
            }]);
        }])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        $blog->incrementViews();

        $relatedBlogs = Blog::where('category_id', $blog->category_id)
            ->where('id', '!=', $blog->id)
            ->published()
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->take(3)
            ->get();

        $userLiked = Auth::check() ? $blog->isLikedBy(Auth::id()) : false;

        return ReactPage::render('blogs.show', compact('blog', 'relatedBlogs', 'userLiked'));
    }

    public function category(string $slug)
    {
        $category = BlogCategory::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $categories = BlogCategory::where('is_active', true)->get();

        $blogs = Blog::with(['category', 'user'])
            ->where('category_id', $category->id)
            ->published()
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->paginate(12);

        $featuredBlogs = Blog::with(['category', 'user'])
            ->published()
            ->where('is_featured', true)
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->take(3)
            ->get();

        return ReactPage::render('blogs.category', compact('category', 'categories', 'blogs', 'featuredBlogs'));
    }

    public function like(Request $request, Blog $blog)
    {
        abort_unless($blog->is_published && (! $blog->published_at || $blog->published_at->lte(now())), 404);
        if (! Auth::check()) {
            return response()->json(['error' => 'Please login to like'], 401);
        }

        $like = BlogLike::where('blog_id', $blog->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($like) {
            $like->delete();
            Blog::withoutTimestamps(fn () => $blog->decrement('likes_count'));

            return response()->json(['liked' => false, 'likes_count' => $blog->likes_count]);
        } else {
            BlogLike::create([
                'blog_id' => $blog->id,
                'user_id' => Auth::id(),
            ]);
            Blog::withoutTimestamps(fn () => $blog->increment('likes_count'));

            return response()->json(['liked' => true, 'likes_count' => $blog->likes_count]);
        }
    }

    public function comment(Request $request, Blog $blog)
    {
        abort_unless($blog->is_published && (! $blog->published_at || $blog->published_at->lte(now())), 404);
        if (! Auth::check()) {
            return redirect()->back()->with('error', 'Please login to comment');
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
            'parent_id' => ['nullable', Rule::exists('blog_comments', 'id')->where('blog_id', $blog->id)->where('is_approved', true)],
        ]);

        $validated['blog_id'] = $blog->id;
        $validated['user_id'] = Auth::id();

        BlogComment::create($validated);
        Blog::withoutTimestamps(fn () => $blog->increment('comments_count'));

        return redirect()->back()->with('success', 'Comment added successfully');
    }
}
