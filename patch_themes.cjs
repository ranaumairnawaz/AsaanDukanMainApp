const fs = require('fs');
const path = require('path');

const baseDir = path.join('c:', 'XAMPP', 'htdocs', 'MeriDukan', 'resources', 'js', 'Pages', 'Storefront');
const themes = ['cosmetics', 'decor', 'electronics', 'fashion'];

const quickviewImport = "import QuickViewModal from '@/Pages/Storefront/Shared/QuickViewModal.vue'\n";
const addToCartLogic = `
const quickViewProduct = ref(null)

function addToCart(product) {
  if (product.variants?.length) {
    quickViewProduct.value = product
  } else {
    import('@inertiajs/vue3').then(({ router }) => {
      router.post(\`\${props.tenant.store_url}/cart\`, {
        product_id: product.id,
        quantity: 1,
      }, { preserveScroll: true })
    })
  }
}
`;

const relatedProductsTemplate = `
    <!-- Related Products -->
    <section v-if="related_products?.length" class="max-w-7xl mx-auto px-4 sm:px-6 py-12 border-t mt-12" style="border-color: var(--color-border);">
      <h2 class="text-2xl font-bold mb-8" style="font-family: var(--font-heading); color: var(--color-text);">You Might Also Like</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        <Link v-for="rel in related_products" :key="rel.id" :href="\`\${tenant.store_url}/product/\${rel.id}\`"
          class="group rounded-xl overflow-hidden block border transition-all hover:-translate-y-1" style="background: var(--color-surface); border-color: var(--color-border);">
          <div class="aspect-square bg-black/5 p-2">
            <img v-if="rel.images?.length" :src="productImageUrl(rel.images[0])" class="w-full h-full object-cover rounded-lg group-hover:scale-105 transition-transform" />
            <div v-else class="w-full h-full flex items-center justify-center text-4xl opacity-30">📦</div>
          </div>
          <div class="p-3">
            <h3 class="text-sm font-semibold truncate" style="color: var(--color-text);">{{ rel.name }}</h3>
            <p class="text-sm font-bold mt-1" :style="{ color: theme?.accent ?? 'var(--color-accent)' }">Rs. {{ Number(rel.price).toLocaleString() }}</p>
          </div>
        </Link>
      </div>
    </section>
`;

// Patch basic Home.vue
const basicHomePath = path.join(baseDir, 'basic', 'Home.vue');
if (fs.existsSync(basicHomePath)) {
    let content = fs.readFileSync(basicHomePath, 'utf8');
    if (!content.includes('Add to Cart') && content.includes('<!-- Product grid -->')) {
        let oldCard = `<button v-for="product in filtered" :key="product.id"\n            @click="openQuickView(product)" class="block group text-left w-full">`;
        let newCard = `<div v-for="product in filtered" :key="product.id" class="block group text-left w-full cursor-pointer relative" @click="openQuickView(product)">`;
        
        content = content.replace(oldCard, newCard);
        
        content = content.replace("</button>\n        </div>\n        <div v-else", `
            <button @click.stop.prevent="openQuickView(product)" class="mt-3 w-full py-2 rounded-lg text-sm font-bold shadow-sm active:scale-95 transition-all" :style="{ background: theme?.accent ?? 'var(--color-accent)', color: '#fff' }">
              Add to Cart
            </button>
          </div>\n        </div>\n        <div v-else`);
          
        fs.writeFileSync(basicHomePath, content, 'utf8');
        console.log('Patched basic/Home.vue');
    }
}

for (const theme of themes) {
    const homePath = path.join(baseDir, theme, 'Home.vue');
    if (fs.existsSync(homePath)) {
        let content = fs.readFileSync(homePath, 'utf8');
        if (!content.includes('QuickViewModal')) {
            if (content.includes('import { productImageUrl')) {
                content = content.replace('import { productImageUrl', quickviewImport + 'import { productImageUrl');
            }
            content = content.replace('</script>', addToCartLogic + '\n</script>');
            content = content.replace('</template>', '  <QuickViewModal :product="quickViewProduct" :tenant="tenant" :theme="theme" @close="quickViewProduct = null" />\n</template>');
            
            content = content.replace(
                /<Link\s+v-for="product in filtered"\s+:key="product\.id"\s+:href="`\$\{tenant\.store_url\}\/product\/\$\{product\.id\}`"\s+class="group block">/g,
                `<div v-for="product in filtered" :key="product.id" @click="import('@inertiajs/vue3').then(({ router }) => router.visit(\`\${tenant.store_url}/product/\${product.id}\`))" class="group block cursor-pointer relative">`
            );
            
            if (content.includes('View Details')) {
                content = content.replace(
                    `<div class="bg-[var(--color-surface)]/90 backdrop-blur-sm text-center py-2.5 rounded-full text-xs font-semibold tracking-wide uppercase" style="color: var(--color-text);">
                  View Details
                </div>`,
                    `<div class="flex gap-2">
                  <button @click.stop.prevent="quickViewProduct = product" class="flex-1 bg-[var(--color-surface)]/90 backdrop-blur-sm text-center py-2.5 rounded-full text-xs font-semibold uppercase hover:bg-white transition-colors" style="color: var(--color-text);">
                    Quick View
                  </button>
                  <button @click.stop.prevent="addToCart(product)" class="flex-1 text-center py-2.5 rounded-full text-xs font-semibold uppercase hover:opacity-90 transition-opacity shadow-lg" :style="{ background: theme?.accent ?? 'var(--color-accent)', color: '#fff' }">
                    Add to Cart
                  </button>
                </div>`
                );
                content = content.replace('</Link>', '</div>');
            } else {
                content = content.replace(
                    /(Rs\. \{\{ Number\(product\.price\)\.toLocaleString\(\) \}\}.*?<\/div>)/g,
                    `$1\n              <div class="mt-4 flex gap-2"><button @click.stop.prevent="quickViewProduct = product" class="flex-1 py-2 text-xs font-bold uppercase rounded-lg bg-gray-100" style="color: var(--color-text);">Quick View</button><button @click.stop.prevent="addToCart(product)" class="flex-1 py-2 text-xs font-bold uppercase rounded-lg" :style="{ background: theme?.accent ?? 'var(--color-accent)', color: '#fff' }">Add to Cart</button></div>`
                );
                if (content.includes('<div v-for="product')) {
                    content = content.replace(/(<div v-for="product.*?)<\/Link>/s, '$1</div>');
                }
            }
            
            fs.writeFileSync(homePath, content, 'utf8');
            console.log(`Patched ${theme}/Home.vue`);
        }
    }
    
    const productPath = path.join(baseDir, theme, 'Product.vue');
    if (fs.existsSync(productPath)) {
        let content = fs.readFileSync(productPath, 'utf8');
        if (!content.includes('You Might Also Like')) {
            content = content.replace('</template>', relatedProductsTemplate + '\n</template>');
            if (!content.includes('related_products')) {
                content = content.replace('tenant: Object,', 'tenant: Object,\n  related_products: Array,');
            }
            if (content.includes('import { Head') && !content.includes('import { Link')) {
                content = content.replace('import { Head', 'import { Head, Link');
            }
            if (!content.includes('import { productImageUrl')) {
                content = content.replace('import { Head', "import { productImageUrl } from '@/utils/productImage'\nimport { Head");
            }
            fs.writeFileSync(productPath, content, 'utf8');
            console.log(`Patched ${theme}/Product.vue`);
        }
    }
}
