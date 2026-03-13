// Получаем продукт
$product = Modules\Product\Models\Product::with(['category', 'images'])->find(1);

// Проверяем основные поля
dump('=== PRODUCT INFO ===');
dump('ID: ' . $product->id);
dump('Name: ' . $product->name);
dump('Price: ' . $product->formatted_price);
dump('In stock: ' . ($product->in_stock ? 'Yes' : 'No'));
dump('Status: ' . $product->status);

// Проверяем категорию
dump('=== CATEGORY ===');
if ($product->category) {
    dump('Category: ' . $product->category->name);
    dump('Full path: ' . $product->category->full_path);
} else {
    dump('No category');
}

// Проверяем изображения
dump('=== IMAGES ===');
dump('Count: ' . $product->images->count());
dump('Main image: ' . ($product->main_image_url ?? 'None'));

foreach ($product->images as $index => $image) {
    dump("Image {$index}:");
    dump("  - URL: {$image->url}");
    dump("  - Thumb: {$image->thumbnail_url}");
    dump("  - Is main: " . ($image->is_main ? 'Yes' : 'No'));
}

// Проверяем аксессоры
dump('=== ACCESSORS ===');
dump('Formatted price: ' . $product->formatted_price);
dump('In stock: ' . ($product->in_stock ? 'true' : 'false'));
dump('Status: ' . $product->status);
dump('Main image URL: ' . ($product->main_image_url ?? 'null'));

// Проверяем загрузку отношений
$product->load('category');
$product->load('images');

dump('=== LOADED RELATIONSHIPS ===');
dump('Category loaded: ' . ($product->relationLoaded('category') ? 'Yes' : 'No'));
dump('Images loaded: ' . ($product->relationLoaded('images') ? 'Yes' : 'No'));
