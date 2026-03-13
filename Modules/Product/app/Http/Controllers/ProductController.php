<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\DTOs\ProductData;
use Modules\Product\Exceptions\ProductNotFoundException;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Http\Requests\UpdateStockRequest;
use Modules\Product\Http\Resources\ProductCollection;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Interfaces\ProductRepositoryInterface;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductService $productService,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(): ProductCollection
    {
        $this->logger->info('Fetching paginated products list');

        $perPage = (int) (request()->get('per_page', 15));
        $products = $this->productRepository->getAllPaginated($perPage);

        return new ProductCollection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $productData = ProductData::fromRequest($request);
            $product = $this->productRepository->create($productData);

            $this->logger->info('Product created via API', ['id' => $product->id]);

            return (new ProductResource($product))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            $this->logger->error('Product creation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Failed to create product',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Request $request, int $id): ProductResource|JsonResponse
    {
        try {
            $product = $this->productRepository->findByIdOrFail($id);

            $this->logger->info('Product retrieved', ['id' => $id]);

//            if ($this->hasOption($request, 'with-category')) {
//                $product->load('category');
//            }
//
//            if ($this->hasOption($request, 'with-images')) {
//                $product->load('images');
//            }
//
//            if ($this->hasOption($request, 'with-dimensions')) {
//                // Можно передать флаг в ресурс через request
//                $request->merge(['with_dimensions' => true]);
//            }

            return new ProductResource($product);
        } catch (ProductNotFoundException $e) {
            $this->logger->warning('Product not found', ['id' => $id]);

            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to retrieve product', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve product',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateProductRequest $request, int $id): ProductResource|JsonResponse
    {
        try {
            $productData = ProductData::fromRequest($request);
            $product = $this->productRepository->update($id, $productData);

            $this->logger->info('Product updated via API', ['id' => $id]);

            return new ProductResource($product);
        } catch (ProductNotFoundException $e) {
            $this->logger->warning('Product not found for update', ['id' => $id]);

            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to update product', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update product',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->productRepository->delete($id);

            if (!$deleted) {
                return response()->json([
                    'message' => 'Product could not be deleted',
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->logger->info('Product deleted via API', ['id' => $id]);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ProductNotFoundException $e) {
            $this->logger->warning('Product not found for deletion', ['id' => $id]);

            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to delete product', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete product',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function active(): ProductCollection
    {
        $this->logger->info('Fetching active products');

        $products = $this->productRepository->getActiveProducts();

        return new ProductCollection($products);
    }

    public function stock(int $id, UpdateStockRequest $request): JsonResponse
    {
        try {
            $quantity = (int) $request->validated('quantity');
            $product = $this->productRepository->updateStock($id, $quantity);

            return response()->json([
                'message' => 'Stock updated successfully',
                'stock_quantity' => $product->stock_quantity,
            ]);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to update stock', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update stock',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function duplicate(Request $request, int $id): JsonResponse
    {
        try {
            $product = $this->productRepository->findByIdOrFail($id);

            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'copy_images' => ['sometimes', 'boolean'],
            ]);

            $duplicated = $this->productService->duplicate($product, [
                'name' => $validated['name'] ?? null,
                'copy_images' => $validated['copy_images'] ?? false,
            ]);

            $this->logger->info('Product duplicated via API', [
                'original_id' => $id,
                'new_id' => $duplicated->id,
            ]);

            return response()->json([
                'message' => 'Product duplicated successfully',
                'data' => new ProductResource($duplicated),
            ], Response::HTTP_CREATED);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to duplicate product', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to duplicate product',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
