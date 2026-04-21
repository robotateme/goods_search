<?php
declare(strict_types=1);

namespace Infrastructure\Search;

use Application\Contracts\Search\ProductSearch;
use App\Models\Product as ProductModel;
use Domain\Product\ProductPage;
use Domain\Product\ProductSearchCriteria;
use Infrastructure\Persistence\ProductModelMapper;
use Infrastructure\Persistence\ProductSearchQueryAdapter;

class DatabaseProductSearch implements ProductSearch
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
            ->paginate($criteria->perPage, ['*'], 'page', $criteria->page);

        return new ProductPage(
            $paginator->getCollection()->map(fn (ProductModel $product) => $this->mapper->map($product))->values()->all(),
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
        );
    }
}
