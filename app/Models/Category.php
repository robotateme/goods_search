<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 *
 * @method static \Illuminate\Database\Eloquent\Builder<self> query()
 */
class Category extends Model
{
    /**
     * @use HasFactory<\Database\Factories\CategoryFactory>
     */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
