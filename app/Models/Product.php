<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $price
 * @property int $category_id
 * @property bool $in_stock
 * @property float $rating
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<self> query()
 * @method static Builder<self> whereIn(string $column, mixed $values)
 * @method static Builder<self> orderBy(string $column, string $direction = 'asc')
 */
class Product extends Model
{
    /**
     * @use HasFactory<\Database\Factories\ProductFactory>
     */
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'category_id',
        'in_stock',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'in_stock' => 'boolean',
            'rating' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearchName(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where('name', 'like', '%'.$term.'%');
    }
}
