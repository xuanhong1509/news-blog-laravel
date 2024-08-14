<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\News;
use App\Models\Comment;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $body['title'] = 'Trang chủ';

        $mainPost = News::latest()->first();
        $subPostsFirstPart = News::latest()->skip(1)->take(3)->get();
        $subPostsSecondPart = News::latest()->skip(4)->take(3)->get();

        $travelPosts = News::where('category_id', 12)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        $data = [
            'mainPost' => $mainPost,
            'subPostsFirstPart' => $subPostsFirstPart,
            'subPostsSecondPart' => $subPostsSecondPart,
            // 'latestTechPost' => $latestTechPost,
            'travelPosts' => $travelPosts,
            'body' => $body,
        ];
        return view('frontend.pages.home', $data);
    }
    public function show($slug)
    {
        // Lấy bài viết và danh mục liên quan
        $news = News::where('slug', $slug)
            ->with('category') // Tải thông tin danh mục
            ->firstOrFail(); // Nếu không tìm thấy sẽ ném lỗi 404

        // Lấy các bình luận liên quan đến bài viết và phân trang các bình luận chính
        // Sắp xếp theo thời gian tạo mới nhất trước
        $comments = Comment::where('news_id', $news->id)
            ->whereNull('parent_id') // Chỉ lấy các bình luận chính
            ->with('user') // Tải thông tin người dùng
            ->orderBy('created_at', 'desc') // Sắp xếp bình luận mới nhất trước
            ->paginate(6); // Phân trang với 6 bình luận mỗi trang

        // Lấy tất cả các phản hồi cho các bình luận chính trên trang hiện tại
        $commentIds = $comments->pluck('id');
        $replies = Comment::whereIn('parent_id', $commentIds)
            ->with('user') // Tải thông tin người dùng
            ->get()
            ->groupBy('parent_id'); // Nhóm các phản hồi theo parent_id

        // Chuẩn bị dữ liệu cho view
        $news->increment('views');
        $data = [
            'body' => ['title' => $news->title],
            'news' => $news,
            'comments' => $comments,
            'replies' => $replies,
            'category_arr' => $news->category // Lấy thông tin danh mục từ bài viết
        ];

        // Trả về view với dữ liệu đã chuẩn bị
        return view('frontend.pages.single-post', $data);
    }

    public function single_category($slug = '')
    {

        $category_arr = DB::table('categories')->where('slug', $slug)->first();

        if (!$category_arr) {
            abort(404, 'Category not found');
        }

        $list_news = DB::table('news')->where('category_id', $category_arr->id)->paginate(5);

        $body['title'] = $category_arr->category_name;
        $data = [
            'slug' => $slug,
            'list_news' => $list_news,
            'category_arr' => $category_arr,
            'body' => $body,
        ];

        return view('frontend.pages.single-category', $data);
    }

    public function allPosts()
    {
        $body['title'] = 'Tin Mới';

        $list_news = News::paginate(10); // Lấy 10 bài viết mỗi trang
        return view('frontend.pages.all-posts', compact('list_news', 'body'));
    }

    public function searchResults(Request $request)
    {
        $query = $request->input('query');
        $body['title'] = 'Kết quả tìm kiếm cho: ' . $query;

        $results = DB::table('news')
            ->join('categories', 'news.category_id', '=', 'categories.id')
            ->select('news.*', 'categories.category_name')
            ->where('news.title', 'LIKE', '%' . $query . '%')
            ->orWhere('news.summary', 'LIKE', '%' . $query . '%')
            ->orWhere('news.content', 'LIKE', '%' . $query . '%')
            ->get();
        $data = ['query' => $query, 'results' => $results];
        return view('frontend.pages.search-result', compact('data', 'body'));
    }

}