$category = Modules\Catalogue\Models\Category::with('parent')->first();
$category->full_path; // Должно работать
$category->level; // Должно работать
$category->breadcrumbs; // Должно работать

// Проверяем продукт с категорией
$product = Modules\Product\Models\Product::with('category')->first();
$product->category?->full_path; // Должно работать
$product->category?->level; // Должно работать

// Загружаем продукт с изображениями
$product = Modules\Product\Models\Product::with('images')->first();

// Смотрим что у нас есть
$product;  // выводит объект

// Проверяем аксессоры (все с $)
$product->formatted_price;   // "999.99"
$product->in_stock;          // true
$product->status;            // "in_stock"
$product->main_image_url;    // null

// Проверяем отношения
$product->images;            // пустая коллекция
$product->category;          // null или объект

// Создаем изображение для теста
$image = $product->images()->create([
    'path' => 'products/1/test.jpg',
    'is_main' => true
]);

// Проверяем снова
$product->fresh()->main_image_url;  // должен вернуть URL

// Выходим из tinker
exit
