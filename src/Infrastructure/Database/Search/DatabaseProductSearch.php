<?php
declare(strict_types=1);

namespace Infrastructure\Database\Search;

use Application\Contracts\Search\ProductSearch;
use App\Models\Product as ProductModel;
use Domain\Product\Search\ProductPage;
use Domain\Product\Search\ProductSearchCriteria;
use Domain\Product\ValueObject\Page;
use Domain\Product\ValueObject\PerPage;
use Infrastructure\Database\ProductModelMapper;
use Infrastructure\Database\ProductSearchQueryAdapter;

final readonly class DatabaseProductSearch implements ProductSearch
{
    public function __construct(
        private readonly ProductSearchQueryAdapter $queryAdapter,
        private readonly ProductModelMapper $mapper,
    ) {
    }

    public function search(ProductSearchCriteria $criteria): ProductPage
    {
        $paginator = $this->queryAdapter
            ->build($criteria)
            ->paginate($criteria->perPage->value(), ['*'], 'page', $criteria->page->value());
        $items = [];

        foreach ($paginator->getCollection() as $product) {
            if ($product instanceof ProductModel) {
                $items[] = $this->mapper->map($product);
            }
        }

        return new ProductPage(
            $items,
            $paginator->total(),
            new PerPage($paginator->perPage()),
            new Page($paginator->currentPage()),
        );
    }
}
