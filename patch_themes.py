import os
import re

base_dir = r"c:\XAMPP\htdocs\MeriDukan\resources\js\Pages\Storefront"
themes = ["cosmetics", "decor", "electronics", "fashion"]

quickview_import = "import QuickViewModal from '@/Pages/Storefront/Shared/QuickViewModal.vue'\n"
add_to_cart_logic = """
const quickViewProduct = ref(null)

function addToCart(product) {
  if (product.variants?.length) {
    quickViewProduct.value = product
  } else {
    import('@inertiajs/vue3').then(({ router }) => {
      router.post(`${props.tenant.store_url}/cart`, {
        product_id: product.id,
        quantity: 1,
      }, { preserveScroll: true })
    })
  }
}
"""

related_products_template = """
    <!-- Related Products -->
    <section v-if="related_products?.length" class="max-w-7xl mx-auto px-4 sm:px-6 py-12 border-t mt-12" style="border-color: var(--color-border);">
      <h2 class="text-2xl font-bold mb-8" style="font-family: var(--font-heading); color: var(--color-text);">You Might Also Like</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        <Link v-for="rel in related_products" :key="rel.id" :href="`${tenant.store_url}/product/${rel.id}`"
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
"""

# Patch basic Home.vue manually via script
basic_home_path = os.path.join(base_dir, "basic", "Home.vue")
if os.path.exists(basic_home_path):
    with open(basic_home_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if "Add to Cart" not in content and "<!-- Product grid -->" in content:
        # replace the button rendering product
        old_card = """<button v-for="product in filtered" :key="product.id"\n            @click="openQuickView(product)" class="block group text-left w-full">"""
        new_card = """<div v-for="product in filtered" :key="product.id" class="block group text-left w-full cursor-pointer relative" @click="openQuickView(product)">"""
        
        content = content.replace(old_card, new_card)
        
        # replace ending button with div and append Add to Cart button
        content = content.replace("</button>\n        </div>\n        <div v-else", """
            <button @click.stop.prevent="openQuickView(product)" class="mt-3 w-full py-2 rounded-lg text-sm font-bold shadow-sm active:scale-95 transition-all" :style="{ background: theme?.accent ?? 'var(--color-accent)', color: '#fff' }">
              Add to Cart
            </button>
          </div>\n        </div>\n        <div v-else""")
        
        with open(basic_home_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print("Patched basic/Home.vue")


for theme in themes:
    # Patch Home.vue
    home_path = os.path.join(base_dir, theme, "Home.vue")
    if os.path.exists(home_path):
        with open(home_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if "QuickViewModal" not in content:
            # Inject import
            if "import { productImageUrl" in content:
                content = content.replace("import { productImageUrl", f"{quickview_import}import {{ productImageUrl")
            
            # Inject logic before </script>
            content = content.replace("</script>", f"{add_to_cart_logic}\n</script>")
            
            # Inject modal at end of template
            content = content.replace("</template>", "  <QuickViewModal :product=\"quickViewProduct\" :tenant=\"tenant\" :theme=\"theme\" @close=\"quickViewProduct = null\" />\n</template>")
            
            # Replace Link with div for the card, and add the buttons.
            # We will use regex to find `<Link v-for="product in filtered" ... class="group block">` and change it
            content = re.sub(
                r'<Link\s+v-for="product in filtered"\s+:key="product\.id"\s+:href="`\${tenant\.store_url}/product/\${product\.id}`"\s+class="group block">',
                r'<div v-for="product in filtered" :key="product.id" @click="import(\'@inertiajs/vue3\').then(({ router }) => router.visit(`${tenant.store_url}/product/${product.id}`))" class="group block cursor-pointer relative">',
                content
            )
            
            # Change the closing </Link> for the product card to </div>
            # We'll just replace the last </Link> in the product grid section. Wait, there might be other Links.
            # Let's inject the buttons at the bottom of the card content.
            # Usually the card has `<div class="px-1">...</div>` or similar. Let's append after the price.
            # A simpler way is to replace `<span v-if="product.compare_at_price" ... </span>\n              </div>\n            </div>\n          </Link>`
            
            # Actually, I'll just append it to the absolute overlay if "View Details" exists (cosmetics, fashion)
            if "View Details" in content:
                content = content.replace(
                    """<div class="bg-[var(--color-surface)]/90 backdrop-blur-sm text-center py-2.5 rounded-full text-xs font-semibold tracking-wide uppercase" style="color: var(--color-text);">
                  View Details
                </div>""",
                    """<div class="flex gap-2">
                  <button @click.stop.prevent="quickViewProduct = product" class="flex-1 bg-[var(--color-surface)]/90 backdrop-blur-sm text-center py-2.5 rounded-full text-xs font-semibold uppercase hover:bg-white transition-colors" style="color: var(--color-text);">
                    Quick View
                  </button>
                  <button @click.stop.prevent="addToCart(product)" class="flex-1 text-center py-2.5 rounded-full text-xs font-semibold uppercase hover:opacity-90 transition-opacity shadow-lg" :style="{ background: theme?.accent ?? 'var(--color-accent)', color: '#fff' }">
                    Add to Cart
                  </button>
                </div>"""
                )
                content = content.replace("</Link>", "</div>", 1) # assuming we replaced Link with div earlier
            else:
                # for decor, electronics which might not have "View Details" overlay
                # I will just inject the buttons at the end of the card.
                content = re.sub(
                    r'(Rs\. \{\{ Number\(product\.price\)\.toLocaleString\(\) \}\}.*?</div>)',
                    r'\1\n              <div class="mt-4 flex gap-2"><button @click.stop.prevent="quickViewProduct = product" class="flex-1 py-2 text-xs font-bold uppercase rounded-lg bg-gray-100" style="color: var(--color-text);">Quick View</button><button @click.stop.prevent="addToCart(product)" class="flex-1 py-2 text-xs font-bold uppercase rounded-lg" :style="{ background: theme?.accent ?? \'var(--color-accent)\', color: \'#fff\' }">Add to Cart</button></div>',
                    content,
                    flags=re.DOTALL
                )
                if '<div v-for="product' in content:
                    # we must have replaced Link. So replace the first </Link> after it with </div>
                    content = re.sub(r'(<div v-for="product.*?)</Link>', r'\1</div>', content, count=1, flags=re.DOTALL)
            
            with open(home_path, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Patched {theme}/Home.vue")
            
    # Patch Product.vue
    product_path = os.path.join(base_dir, theme, "Product.vue")
    if os.path.exists(product_path):
        with open(product_path, 'r', encoding='utf-8') as f:
            content = f.read()
            
        if "You Might Also Like" not in content:
            content = content.replace("</template>", f"{related_products_template}\n</template>")
            
            # Add related_products to props if not there
            if "related_products" not in content:
                content = content.replace("tenant: Object,", "tenant: Object,\n  related_products: Array,")
                
            # Import Link if not there
            if "import { Head" in content and "import { Link" not in content:
                content = content.replace("import { Head", "import { Head, Link")
                
            # Import productImageUrl if not there
            if "import { productImageUrl" not in content:
                content = content.replace("import { Head", "import { productImageUrl } from '@/utils/productImage'\nimport { Head")
                
            with open(product_path, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Patched {theme}/Product.vue")
