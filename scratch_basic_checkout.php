<?php
$f = 'c:/XAMPP/htdocs/MeriDukan/resources/js/Pages/Storefront/basic/Checkout.vue';
$c = file_get_contents($f);
$c = str_replace('`/storage/${item.image}`', 'productImageUrl(item.image)', $c);
if (strpos($c, "import { productImageUrl }") === false) {
    $c = str_replace("import { useForm, router } from '@inertiajs/vue3'", "import { useForm, router } from '@inertiajs/vue3'\nimport { productImageUrl } from '@/utils/productImage'", $c);
}
file_put_contents($f, $c);
echo "Done replacing basic/Checkout.vue\n";
