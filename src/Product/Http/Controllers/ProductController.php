<?php

namespace Larasell\Admin\Product\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Product\Data\View\ProductsPageData;
use Larasell\Admin\Product\Tables\Products;

class ProductController
{
    public function index(): Response
    {
        return Inertia::render('Product/Index', new ProductsPageData(
            products: Products::make(),
        ));
    }
}
