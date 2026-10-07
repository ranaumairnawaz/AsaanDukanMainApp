<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\Product;
use Illuminate\Support\Str;

// Category to theme slug mapping
$themeCategories = [
    'cosmetics'   => 'Cosmetics',
    'fashion'     => 'Fashion',
    'decor'       => 'Home Decor',
    'electronics' => 'Electronics',
    'basic'       => 'General',
];

// Images per category using picsum for realistic-looking product images
$categoryImages = [
    'Cosmetics' => [
        'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=600&q=80',
        'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=600&q=80',
        'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=600&q=80',
        'https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?w=600&q=80',
        'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?w=600&q=80',
        'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=600&q=80',
        'https://images.unsplash.com/photo-1631730486784-74757d59a1b7?w=600&q=80',
        'https://images.unsplash.com/photo-1503236823255-94609f598e71?w=600&q=80',
    ],
    'Fashion' => [
        'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&q=80',
        'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&q=80',
        'https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=600&q=80',
        'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=600&q=80',
        'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?w=600&q=80',
        'https://images.unsplash.com/photo-1582142306909-195724d33ffc?w=600&q=80',
        'https://images.unsplash.com/photo-1606760227091-3dd870d97f1d?w=600&q=80',
        'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600&q=80',
    ],
    'Home Decor' => [
        'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&q=80',
        'https://images.unsplash.com/photo-1540932239986-30128078f3c5?w=600&q=80',
        'https://images.unsplash.com/photo-1616046229478-9901c5536a45?w=600&q=80',
        'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=600&q=80',
        'https://images.unsplash.com/photo-1567016432779-094069958ea5?w=600&q=80',
        'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?w=600&q=80',
        'https://images.unsplash.com/photo-1506439773649-6e0eb8cfb237?w=600&q=80',
        'https://images.unsplash.com/photo-1484101403633-562f891dc89a?w=600&q=80',
    ],
    'Electronics' => [
        'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=600&q=80',
        'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600&q=80',
        'https://images.unsplash.com/photo-1585155784229-aff921ccfa4f?w=600&q=80',
        'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=600&q=80',
        'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=600&q=80',
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&q=80',
        'https://images.unsplash.com/photo-1625842268584-8f3296236761?w=600&q=80',
        'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=600&q=80',
    ],
    'General' => [
        'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80',
        'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&q=80',
        'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=600&q=80',
        'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=600&q=80',
        'https://images.unsplash.com/photo-1520259877745-c6eb05aefb37?w=600&q=80',
        'https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=600&q=80',
        'https://images.unsplash.com/photo-1528360983277-13d401cdc186?w=600&q=80',
        'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&q=80',
    ],
];

// Product names per category
$productNames = [
    'Cosmetics' => [
        'Hydra Glow Serum', 'Rose Lip Balm', 'Velvet Foundation', 'Brightening Eye Cream',
        'Matte Lipstick Set', 'Vitamin C Face Wash', 'Luxury Face Mask', 'Pearl Highlighter',
    ],
    'Fashion' => [
        'Floral Lawn Suit', 'Embroidered Kurta', 'Classic Denim Jacket', 'Silk Evening Dress',
        'Printed Palazzo Set', 'Casual Linen Shirt', 'Designer Handbag', 'Boho Maxi Skirt',
    ],
    'Home Decor' => [
        'Ceramic Vase Set', 'Macramé Wall Art', 'Woven Table Runner', 'Scented Candle Pack',
        'Decorative Throw Pillow', 'Wooden Photo Frame', 'Rattan Lamp Shade', 'Marble Coaster Set',
    ],
    'Electronics' => [
        'Wireless Earbuds', 'Smart Watch Pro', 'Portable Power Bank', 'USB-C Fast Charger',
        'Bluetooth Speaker', 'HD Webcam 1080p', 'Gaming Mouse', 'LED Desk Lamp',
    ],
    'General' => [
        'Premium Gift Box', 'Stainless Steel Bottle', 'Eco-friendly Tote Bag', 'Personalized Notebook',
        'Yoga Mat Set', 'Minimalist Wall Clock', 'Travel Organizer', 'Aromatherapy Diffuser',
    ],
];

// Descriptions per category
$productDescriptions = [
    'Cosmetics' => 'Premium quality beauty product crafted with the finest ingredients for radiant, healthy skin.',
    'Fashion' => 'Elegant, stylish, and comfortable — designed to make you stand out with timeless fashion.',
    'Home Decor' => 'Transform your living space with this carefully crafted decorative piece.',
    'Electronics' => 'High-performance gadget engineered for reliability, speed, and your modern lifestyle.',
    'General' => 'Versatile, premium-quality product crafted for everyday use.',
];

$tenants = Tenant::with('themeSettings')->get();

foreach ($tenants as $tenant) {
    $themeSlug = $tenant->themeSettings?->theme_slug ?? 'basic';
    $category = $themeCategories[$themeSlug] ?? 'General';
    $images = $categoryImages[$category];
    $names = $productNames[$category];
    $description = $productDescriptions[$category];

    // Delete old demo products (those starting with "Demo Product")
    Product::where('tenant_id', $tenant->id)
        ->where('name', 'like', 'Demo Product%')
        ->delete();

    echo "Seeding {$tenant->name} (theme: {$themeSlug}, category: {$category})\n";

    foreach ($names as $i => $name) {
        Product::create([
            'tenant_id'       => $tenant->id,
            'name'            => $name,
            'slug'            => Str::slug($name) . '-' . $tenant->id . '-' . ($i + 1),
            'description'     => $description,
            'price'           => rand(200, 4500),
            'compare_at_price'=> rand(0, 1) ? rand(4600, 9000) : null,
            'stock_quantity'  => rand(5, 50),
            'sku'             => strtoupper(Str::random(8)),
            'category'        => $category,
            'images'          => [$images[$i]],
            'is_active'       => true,
        ]);
    }

    echo "  -> Added 8 products\n";
}

echo "\nDone!\n";
