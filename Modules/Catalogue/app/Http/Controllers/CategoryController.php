<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalogue\DTOs\CategoryData;
use Modules\Catalogue\Exceptions\CategoryNotFoundException;
use Modules\Catalogue\Http\Requests\StoreCategoryRequest;
use Modules\Catalogue\Http\Requests\UpdateCategoryRequest;
use Modules\Catalogue\Http\Resources\CategoryCollection;
use Modules\Catalogue\Http\Resources\CategoryResource;
use Modules\Catalogue\Http\Traits\HasQueryOptions;
use Modules\Catalogue\Interfaces\CategoryRepositoryInterface;
use Modules\Catalogue\Models\Category;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class CategoryController extends Controller
{
    use HasQueryOptions;

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly LoggerInterface             $logger,
    )
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): CategoryCollection
    {
        $this->logger->info('Fetching paginated categories list');

        $perPage = (int)(request()->get('per_page', 15));
        $categories = $this->categoryRepository->getAllPaginated($perPage);

        return new CategoryCollection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $categoryData = CategoryData::fromRequest($request);
            $category = $this->categoryRepository->create($categoryData);

            $this->logger->info('Category created via API', ['id' => $category->id]);

            return new CategoryResource($category)
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            $this->logger->error('Category creation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Failed to create category',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Request $request, int $id): CategoryResource|JsonResponse
    {
        try {
            // Формируем опции для загрузки отношений
            $loadOptions = [];
            if ($this->hasOption($request, 'with-parent')) {
                $loadOptions[] = 'with-parent';
            }
            if ($this->hasOption($request, 'with-children')) {
                $loadOptions[] = 'with-children';
            }
            if ($this->hasOption($request, 'with-ancestors')) {
                $loadOptions[] = 'with-ancestors';
            }
            if ($this->hasOption($request, 'with-descendants')) {
                $loadOptions[] = 'with-descendants';
            }

            $category = $this->categoryRepository->getCategoryWithRelationsOrFail($id, $loadOptions);

            $this->logger->info('Category retrieved', [
                'id' => $id,
                'options' => $this->getOptionsAsString($request)
            ]);

            return new CategoryResource($category);
        } catch (CategoryNotFoundException $e) {
            $this->logger->warning('Category not found', ['id' => $id]);
            return response()->json(['message' => 'Category not found'], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to retrieve category', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to retrieve category'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource|JsonResponse
    {
        try {
            $categoryData = CategoryData::fromRequest($request);
            $category = $this->categoryRepository->update($category->id, $categoryData);

            $this->logger->info('Category updated via API', ['id' => $category->id]);

            return new CategoryResource($category);
        } catch (CategoryNotFoundException $e) {
            $this->logger->warning('Category not found for update', ['id' => $category->id]);

            return response()->json([
                'message' => 'Category not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to update category', [
                'id' => $category->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update category',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->categoryRepository->delete($id);

            if (!$deleted) {
                return response()->json([
                    'message' => 'Category could not be deleted',
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->logger->info('Category deleted via API', ['id' => $id]);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (CategoryNotFoundException $e) {
            $this->logger->warning('Category not found for deletion', ['id' => $id]);

            return response()->json([
                'message' => 'Category not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to delete category', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete category',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function active(): CategoryCollection
    {
        $this->logger->info('Fetching active categories');

        $categories = $this->categoryRepository->getActiveCategories();

        return new CategoryCollection($categories);
    }
}
