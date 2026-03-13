<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Product\Exceptions\ProductNotFoundException;
use Modules\Product\Http\Requests\StoreProductImageRequest;
use Modules\Product\Http\Requests\UpdateProductImageRequest;
use Modules\Product\Http\Resources\ProductImageCollection;
use Modules\Product\Http\Resources\ProductImageResource;
use Modules\Product\Interfaces\ProductRepositoryInterface;
use Modules\Product\Models\ProductImage;
use Modules\Product\Services\ProductImageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class ProductImageController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductImageService $imageService,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(int $productId): ProductImageCollection|JsonResponse
    {
        try {
            $product = $this->productRepository->findByIdOrFail($productId);

            $images = $product->images()
                ->orderBy('sort_order')
                ->orderBy('is_main', 'desc')
                ->get();

            return new ProductImageCollection($images);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to fetch product images', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to fetch images',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(StoreProductImageRequest $request, int $productId): JsonResponse
    {
        try {
            $product = $this->productRepository->findByIdOrFail($productId);

            $image = $this->imageService->uploadImage(
                $product,
                $request->file('image'),
                $request->validated('alt'),
                (bool) $request->validated('is_main', false)
            );

            $this->logger->info('Product image uploaded via API', [
                'product_id' => $productId,
                'image_id' => $image->id,
            ]);

            return new ProductImageResource($image)
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to upload product image', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to upload image',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateProductImageRequest $request, int $productId, int $imageId): ProductImageResource|JsonResponse
    {
        try {
            $product = $this->productRepository->findByIdOrFail($productId);

            /** @var ProductImage|null $image */
            $image = $product->images()->find($imageId);

            if (null === $image) {
                return response()->json([
                    'message' => 'Image not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $validated = $request->validated();

            if (isset($validated['is_main']) && $validated['is_main'] === true) {
                $this->imageService->setMainImage($image);
            }

            if (isset($validated['alt']) || isset($validated['sort_order'])) {
                $image->update($validated);
            }

            $this->logger->info('Product image updated via API', [
                'image_id' => $imageId,
                'product_id' => $productId,
            ]);

            return new ProductImageResource($image->fresh());
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to update product image', [
                'image_id' => $imageId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update image',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $productId, int $imageId): JsonResponse
    {
        try {
            $product = $this->productRepository->findByIdOrFail($productId);

            /** @var ProductImage|null $image */
            $image = $product->images()->find($imageId);

            if (null === $image) {
                return response()->json([
                    'message' => 'Image not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $this->imageService->deleteImage($image);

            $this->logger->info('Product image deleted via API', [
                'image_id' => $imageId,
                'product_id' => $productId,
            ]);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to delete product image', [
                'image_id' => $imageId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete image',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reorder(int $productId, \Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:product_images,id'],
        ]);

        try {
            $product = $this->productRepository->findByIdOrFail($productId);

            $this->imageService->reorderImages($product, $request->input('order'));

            return response()->json([
                'message' => 'Images reordered successfully',
            ]);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to reorder images', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to reorder images',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function setMain(int $productId, int $imageId): JsonResponse
    {
        try {
            $product = $this->productRepository->findByIdOrFail($productId);

            /** @var ProductImage|null $image */
            $image = $product->images()->find($imageId);

            if (null === $image) {
                return response()->json([
                    'message' => 'Image not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $this->imageService->setMainImage($image);

            return response()->json([
                'message' => 'Main image set successfully',
            ]);
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to set main image', [
                'image_id' => $imageId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to set main image',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
