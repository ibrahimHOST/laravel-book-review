<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Book extends Model
{
    use HasFactory;

    // Relations
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }


    // Scope for search
    public function scopeTitle(Builder $query, string $title) : Builder
    {
        return $query->where('title', 'like', '%' . $title . '%');
    }

    // Scope for counting the number of reviews for the books
    public function scopeWithReviewsCount(Builder $query, $from = null , $to = null) : Builder|QueryBuilder
    {
        return $query->withCount([
            'reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ]);
    }
    // Scope for counting the number of reviews for the books with avarge rating
    public function scopeWithAvgRating(Builder $query, $from = null , $to = null) : Builder|QueryBuilder
    {
        return $query->withAvg([
            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ], 'rating');
    }

    // Scope for popular Books
    public function scopePopular(Builder $query) : Builder|QueryBuilder
    {
        return $query->WithReviewsCount()
        ->orderBy('reviews_count', 'desc');
    }


    // Scope for highest rated Books
    public function scopeHighestRated(Builder $query, $from = null , $to = null) : Builder|QueryBuilder
    {
        return $query->WithAvgRating()
            ->orderBy('reviews_avg_rating', 'desc');
    }


    // Scopes for Min Reviews  popular and highest rated Books
    public function scopeMinReviews(Builder $query, $minReviews): Builder|QueryBuilder
    {
        return $query->having('reviews_count', '>=', $minReviews);
    }


    // Scopes for date Range Filter for popular and highest rated Books
    private function dateRangeFilter(Builder $query, $from = null, $to = null)
    {
                if ($from && !$to) {
                    $query->where('created_at', '>=', $from);
                } elseif (!$from && $to) {
                    $query->where('created_at', '<=', $to);
                } elseif ($from && $to) {
                    $query->whereBetween('created_at', [$from, $to]);
                }
    }

    // Scopes for popular and highest rated Books
    public function scopePopularLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonth(), now())
        ->highestRated(now()->subMonth(), now())
        ->minReviews(2);
    }


    public function scopePopularLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonths(6), now())
        ->highestRated(now()->subMonths(6), now())
        ->minReviews(5);
    }


    public function scopeHighestRatedLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonth(), now())
        ->popular(now()->subMonth(), now())
        ->minReviews(2);
    }


    public function scopeHighestRatedLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonths(6), now())
        ->popular(now()->subMonths(6), now())
        ->minReviews(5);
    }

    // This is a way to clear the cache when a review is updated
    protected static function booted()
    {
        static::updated(
            fn (Review $book) => cache()->forget('book:' . $book->id)
        );
        static::deleted(
            fn (Review $book) => cache()->forget('book:' . $book->id)
        );
    }
}
