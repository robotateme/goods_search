<?php

declare(strict_types=1);

namespace Infrastructure\Database\Eloquent;

use Database\Factories\ProductFactory;
use Domain\Product\ValueObject\Price;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string $price
 * @property int $category_id
 * @property bool $in_stock
 * @property float $rating
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static> whereIn(string $column, mixed $values)
 * @method static Builder<static> orderBy(string $column, string $direction = 'asc')
 */
class Product extends Model
{
    /**
     * @use HasFactory<ProductFactory>
     */
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'category_id',
        'in_stock',
        'rating',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
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

    protected function price(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $value): string => Price::fromMinorUnits(self::rawPriceToMinorUnits($value))->value(),
            set: static fn (mixed $value): int => Price::fromInput(is_string($value) || is_int($value) || is_float($value) ? $value : throw new \InvalidArgumentException('Price must be a numeric scalar.'))->minorUnits(),
        );
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    private static function rawPriceToMinorUnits(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw new \InvalidArgumentException('Stored product price must be an integer minor-units value.');
    }
}
