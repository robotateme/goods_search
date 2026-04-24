<?php

declare(strict_types=1);

namespace Infrastructure\Database\Search;

use App\Models\Product as ProductModel;
use Application\Contracts\Search\ProductSearch;
use Domain\Product\Search\ProductPage;
use Domain\Product\Search\ProductSearchCriteria;
use Domain\Product\ValueObject\Page;
use Domain\Product\ValueObject\PerPage;
use Infrastructure\Database\ProductModelMapper;
use Infrastructure\Database\ProductSearchQueryAdapter;
use Override;

final readonly class DatabaseProductSearch implements ProductSearch
{
    public function __construct(
        private readonly ProductSearchQueryAdapter $queryAdapter,
        private readonly ProductModelMapper $mapper,
    ) {}

    #[Override]
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
