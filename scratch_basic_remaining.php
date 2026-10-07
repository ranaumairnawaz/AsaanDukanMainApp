<?php
// Fix basic/Product.vue (related products image)
$f = 'c:/XAMPP/htdocs/MeriDukan/resources/js/Pages/Storefront/basic/Product.vue';
$c = file_get_contents($f);
$c = str_replace('`/storage/${rel.images[0]}`', 'productImageUrl(rel.images[0])', $c);
file_put_contents($f, $c);

// Fix basic/Home.vue (quick view image)
$f = 'c:/XAMPP/htdocs/MeriDukan/resources/js/Pages/Storefront/basic/Home.vue';
$c = file_get_contents($f);
$c = str_replace('`/storage/${quickViewProduct.images[0]}`', 'productImageUrl(quickViewProduct.images[0])', $c);
file_put_contents($f, $c);

echo "Done fixing remaining images\n";
