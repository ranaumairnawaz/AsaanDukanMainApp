<?php
$f = 'c:/XAMPP/htdocs/MeriDukan/resources/js/Pages/Storefront/basic/Checkout.vue';
$c = file_get_contents($f);
if(substr($c,0,3) == "\xEF\xBB\xBF") {
    file_put_contents($f, substr($c,3));
    echo "BOM removed\n";
} else {
    echo "No BOM found\n";
}
