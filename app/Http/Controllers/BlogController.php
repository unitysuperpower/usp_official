<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;

class BlogController extends Controller
{
    public function index()
    {
        SEOMeta::setTitle('Blog');
        SEOMeta::setDescription('Read our latest articles, insights, and tips on technology, business, and innovation.');
        SEOMeta::addKeyword(['blog', 'articles', 'insights', 'tips', 'news']);

        $blogs = Blog::with(['category', 'user'])
            ->where('is_published', true)
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->paginate(12);

        $categories = BlogCategory::where('is_active', true)
            ->withCount('blogs')
            ->get();

        $featuredBlogs = Blog::where('is_published', true)
            ->where('is_featured', true)
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->take(3)
            ->get();

        return \App\Support\ReactPage::render('blogs.index', compact('blogs', 'categories', 'featuredBlogs'));
    }

    public function show(string $slug)
    {
        $blog = Blog::with(['category', 'user', 'comments' => function($query) {
            $query->where('is_approved', true)->whereNull('parent_id')->with(['user', 'replies' => function($q) {
                $q->where('is_approved', true)->with('user');
            }]);
        }])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        SEOMeta::setTitle($blog->title);
        SEOMeta::setDescription(strip_tags(\Illuminate\Support\Str::limit($blog->content, 160)));
        SEOMeta::addKeyword([$blog->title, $blog->category->name, 'blog', 'article']);

        OpenGraph::setTitle($blog->title);
        OpenGraph::setDescription(strip_tags(\Illuminate\Support\Str::limit($blog->content, 200)));
        OpenGraph::setType('article');
        if ($blog->image) {
            OpenGraph::addImage(asset('storage/' . $blog->image));
        }

        TwitterCard::setTitle($blog->title);
        TwitterCard::setDescription(strip_tags(\Illuminate\Support\Str::limit($blog->content, 160)));
        if ($blog->image) {
            TwitterCard::setImage(asset('storage/' . $blog->image));
        }

        $blog->incrementViews();

        $relatedBlogs = Blog::where('category_id', $blog->category_id)
            ->where('id', '!=', $blog->id)
            ->where('is_published', true)
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->take(3)
            ->get();

        $userLiked = Auth::check() ? $blog->isLikedBy(Auth::id()) : false;

        return \App\Support\ReactPage::render('blogs.show', compact('blog', 'relatedBlogs', 'userLiked'));
    }

    public function category(string $slug)
    {
        $category = BlogCategory::where('slug', $slug)->firstOrFail();
        $categories = BlogCategory::where('is_active', true)->get();

        $blogs = Blog::with(['category', 'user'])
            ->where('category_id', $category->id)
            ->where('is_published', true)
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->paginate(12);

        $featuredBlogs = Blog::with(['category', 'user'])
            ->where('is_published', true)
            ->where('is_featured', true)
            ->withCount(['likes', 'comments'])
            ->latest('published_at')
            ->take(3)
            ->get();

        return \App\Support\ReactPage::render('blogs.category', compact('category', 'categories', 'blogs', 'featuredBlogs'));
    }

    public function like(Request $request, Blog $blog)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Please login to like'], 401);
        }

        $like = BlogLike::where('blog_id', $blog->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($like) {
            $like->delete();
            $blog->decrement('likes_count');
            return response()->json(['liked' => false, 'likes_count' => $blog->likes_count]);
        } else {
            BlogLike::create([
                'blog_id' => $blog->id,
                'user_id' => Auth::id(),
            ]);
            $blog->increment('likes_count');
            return response()->json(['liked' => true, 'likes_count' => $blog->likes_count]);
        }
    }

    public function comment(Request $request, Blog $blog)
    {
        if (!Auth::check()) {
            return redirect()->back()->with('error', 'Please login to comment');
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:blog_comments,id',
        ]);

        $validated['blog_id'] = $blog->id;
        $validated['user_id'] = Auth::id();

        BlogComment::create($validated);
        $blog->increment('comments_count');

        return redirect()->back()->with('success', 'Comment added successfully');
    }
}
