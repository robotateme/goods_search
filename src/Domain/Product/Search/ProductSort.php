<?php
declare(strict_types=1);

namespace Domain\Product\Search;

enum ProductSort: string
{
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
    case RatingDesc = 'rating_desc';
    case Newest = 'newest';
}
