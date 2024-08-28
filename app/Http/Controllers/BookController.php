<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the search term from the request, Filtering the books by title
        $title = $request->input('title');
        $filter = $request->input('filter','');
        // Get the books from the database
        $books = Book::when(
            $title,
            // If the search term is not empty, filter the books by title
            fn($query, $title) => $query->title($title)
        );

        $books = match($filter) {
            'popular_last_month' => $books->popularLastMonth(),
            'popular_last_6months' => $books->popularLast6Months(),
            'highest_rated_last_month' => $books->highestRatedLastMonth(),
            'highest_rated_last_6months' => $books->highestRatedLast6Months(),
            default => $books->latest()->withAvgRating()->withReviewsCount()
        };

       //$books = $books->get();

       // Caching the books and filtering by title to avoid duplicate queries and improve performance by caching the results of the query
       $cacheKey = 'books:' . $title . ':' . $filter;
       $books = cache()->remember($cacheKey, 3600, function() use ($books) {
           return $books->get();
       }); // 1 hour chaching

        // Render the view with the books
        return view('books.index', ['books' => $books]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        // declaer the cache key and get the book from the cache
        $chacheKey = 'book:' . $id;
        // get the book from the cache and load the reviews and return the view to the user
        $book = cache()->remember(
            $chacheKey,
            30,
            fn () =>
            Book::with([
            'reviews' => fn ($query) => $query->latest()
        ])->withAvgRating()->WithReviewsCount()->findOrFail($id)

    );

        return view('books.show',['book' => $book]
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
