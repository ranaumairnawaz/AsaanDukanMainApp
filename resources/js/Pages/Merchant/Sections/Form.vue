<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  section: Object,   // null when creating
  type: String,      // e.g. 'promo_banner'
  products: Array,   // all of this tenant's products, for the picker
})

const isEditing = computed(() => !!props.section)
const cfg = props.section?.config ?? {}

const typeLabels = {
  featured_products:  'Featured Products',
  new_arrivals:       'New Arrivals',
  promo_banner:       'Promo Banner',
  testimonials:       'Testimonials',
  category_showcase:  'Category Showcase',
  whatsapp_cta:       'WhatsApp CTA',
  faq:                'FAQ',
}

const form = useForm({
  _method: isEditing.value ? 'put' : 'post',
  type: props.type,
  title: props.section?.title ?? '',
  subtitle: props.section?.subtitle ?? '',
  is_active: props.section?.is_active ?? true,

  // promo_banner
  cta_text: cfg.cta_text ?? '',
  cta_link: cfg.cta_link ?? '#products',
  layout: cfg.layout ?? 'image_right',
  image: null,
  existing_image: cfg.image_path ?? null,

  // new_arrivals
  limit: cfg.limit ?? 8,

  // featured_products
  columns: cfg.columns ?? 4,
  product_ids: props.section?.products?.map(p => p.id) ?? [],

  // testimonials / faq / category_showcase share the `items` shape,
  // each field below is only read for its matching type
  items: (cfg.items ?? []).map(item => ({ ...item, image: null })),
})

// ---- promo banner image preview ----
const bannerPreview = ref(cfg.image_path ? `/storage/${cfg.image_path}` : null)
function handleBannerImage(e) {
  const file = e.target.files[0]
  if (!file) return
  form.image = file
  const reader = new FileReader()
  reader.onload = (ev) => (bannerPreview.value = ev.target.result)
  reader.readAsDataURL(file)
}

// ---- category showcase item image previews ----
const itemPreviews = ref((cfg.items ?? []).map(item => item.image_path ? `/storage/${item.image_path}` : null))
function handleItemImage(e, i) {
  const file = e.target.files[0]
  if (!file) return
  form.items[i].image = file
  form.items[i].existing_image = form.items[i].image_path ?? null
  const reader = new FileReader()
  reader.onload = (ev) => (itemPreviews.value[i] = ev.target.result)
  reader.readAsDataURL(file)
}

function addItem() {
  if (props.type === 'testimonials') {
    form.items.push({ name: '', text: '', rating: 5, location: '' })
  } else if (props.type === 'faq') {
    form.items.push({ question: '', answer: '' })
  } else if (props.type === 'category_showcase') {
    form.items.push({ label: '', category: '', image: null })
    itemPreviews.value.push(null)
  }
}

function removeItem(i) {
  form.items.splice(i, 1)
  itemPreviews.value.splice(i, 1)
}

// ---- featured products picker ----
const productSearch = ref('')
const filteredProducts = computed(() => {
  if (!productSearch.value) return props.products
  const q = productSearch.value.toLowerCase()
  return props.products.filter(p => p.name.toLowerCase().includes(q))
})
function toggleProduct(id) {
  const idx = form.product_ids.indexOf(id)
  if (idx === -1) form.product_ids.push(id)
  else form.product_ids.splice(idx, 1)
}

function submit() {
  const options = { forceFormData: true, preserveScroll: true }
  if (isEditing.value) {
    form.post(route('merchant.sections.update', props.section.id), options)
  } else {
    form.post(route('merchant.sections.store'), options)
  }
}
</script>

<template>
  <MerchantLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('merchant.sections.index')" class="text-gray-500 hover:text-white text-sm transition-colors shrink-0">
          ← Back
        </Link>
        <h1 class="text-base font-semibold text-white truncate">
          {{ isEditing ? 'Edit' : 'Add' }} {{ typeLabels[type] }}
        </h1>
      </div>
    </template>

    <form @submit.prevent="submit" enctype="multipart/form-data" class="max-w-3xl space-y-4">

      <!-- Shared fields -->
      <div class="card p-4 sm:p-6 space-y-4">
        <h2 class="font-bold text-white text-sm">Section Details</h2>

        <div>
          <label class="form-label">Heading <span class="text-gray-600 font-normal">(optional, has a sensible default)</span></label>
          <input v-model="form.title" type="text" placeholder="e.g. What Our Customers Say" class="form-input" />
        </div>

        <div v-if="['promo_banner', 'featured_products', 'whatsapp_cta'].includes(type)">
          <label class="form-label">Subtitle <span class="text-gray-600 font-normal">(optional)</span></label>
          <input v-model="form.subtitle" type="text" placeholder="A short supporting line" class="form-input" />
        </div>

        <div class="flex items-center gap-3">
          <input v-model="form.is_active" id="is_active" type="checkbox" class="w-4 h-4 accent-emerald-500" />
          <label for="is_active" class="text-sm text-gray-300 cursor-pointer">Show this section on the storefront</label>
        </div>
      </div>

      <!-- Promo Banner -->
      <div v-if="type === 'promo_banner'" class="card p-4 sm:p-6 space-y-4">
        <h2 class="font-bold text-white text-sm">Banner Image</h2>
        <div class="flex items-center gap-4">
          <div class="w-32 h-20 rounded-xl overflow-hidden bg-black/20 border border-white/10 shrink-0 flex items-center justify-center">
            <img v-if="bannerPreview" :src="bannerPreview" class="w-full h-full object-cover" />
            <span v-else class="text-2xl opacity-30">🖼️</span>
          </div>
          <input type="file" accept="image/*" @change="handleBannerImage"
            class="text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-white/5 file:text-white hover:file:bg-white/10" />
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Button Text</label>
            <input v-model="form.cta_text" type="text" placeholder="Shop the Sale" class="form-input" />
          </div>
          <div>
            <label class="form-label">Button Link</label>
            <input v-model="form.cta_link" type="text" placeholder="#products" class="form-input" />
          </div>
        </div>

        <div>
          <label class="form-label">Image Position</label>
          <select v-model="form.layout" class="form-input">
            <option value="image_right">Image on the right</option>
            <option value="image_left">Image on the left</option>
          </select>
        </div>
      </div>

      <!-- WhatsApp CTA -->
      <div v-if="type === 'whatsapp_cta'" class="card p-4 sm:p-6 space-y-4">
        <h2 class="font-bold text-white text-sm">Message</h2>
        <div>
          <label class="form-label">Pre-filled WhatsApp Message</label>
          <textarea v-model="form.message" rows="2" placeholder="Hi, I'd like to ask about your products."
            class="form-input resize-none"></textarea>
          <p class="text-[11px] text-gray-500 mt-1">Sent to the WhatsApp number set in Settings when a customer taps the button.</p>
        </div>
      </div>

      <!-- New Arrivals -->
      <div v-if="type === 'new_arrivals'" class="card p-4 sm:p-6 space-y-4">
        <h2 class="font-bold text-white text-sm">Display Settings</h2>
        <div>
          <label class="form-label">How many recent products to show</label>
          <input v-model.number="form.limit" type="number" min="2" max="24" class="form-input sm:w-40" />
          <p class="text-[11px] text-gray-500 mt-1">Automatically pulls your newest active products — no need to update this as you add items.</p>
        </div>
      </div>

      <!-- Featured Products -->
      <div v-if="type === 'featured_products'" class="card p-4 sm:p-6 space-y-4">
        <h2 class="font-bold text-white text-sm">Choose Products</h2>
        <input v-model="productSearch" type="text" placeholder="Search products..." class="form-input" />

        <div class="max-h-80 overflow-y-auto space-y-1.5 border border-white/5 rounded-xl p-2">
          <label v-for="p in filteredProducts" :key="p.id"
            class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer">
            <input type="checkbox" :checked="form.product_ids.includes(p.id)" @change="toggleProduct(p.id)"
              class="w-4 h-4 accent-emerald-500 shrink-0" />
            <div class="w-9 h-9 rounded-lg bg-black/20 overflow-hidden shrink-0">
              <img v-if="p.images?.length" :src="`/storage/${p.images[0]}`" class="w-full h-full object-cover" />
            </div>
            <span class="text-sm text-gray-300 flex-1 truncate">{{ p.name }}</span>
            <span class="text-xs text-emerald-400 shrink-0">Rs. {{ Number(p.price).toLocaleString() }}</span>
          </label>
          <p v-if="filteredProducts.length === 0" class="text-xs text-gray-600 text-center py-4">No products match.</p>
        </div>
        <p class="text-[11px] text-gray-500">{{ form.product_ids.length }} selected — they'll appear in the order you pick them.</p>

        <div>
          <label class="form-label">Columns (desktop)</label>
          <select v-model.number="form.columns" class="form-input sm:w-40">
            <option :value="3">3</option>
            <option :value="4">4</option>
            <option :value="5">5</option>
          </select>
        </div>
      </div>

      <!-- Testimonials -->
      <div v-if="type === 'testimonials'" class="card p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-bold text-white text-sm">Reviews</h2>
          <button type="button" @click="addItem" class="btn-secondary btn-sm">+ Add Review</button>
        </div>

        <div v-for="(item, i) in form.items" :key="i" class="p-3 bg-gray-950 rounded-xl border border-white/5 space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <input v-model="item.name" placeholder="Customer name" class="form-input" />
            <input v-model="item.location" placeholder="City (optional)" class="form-input" />
          </div>
          <textarea v-model="item.text" rows="2" placeholder="What they said..." class="form-input resize-none"></textarea>
          <div class="flex items-center justify-between">
            <select v-model.number="item.rating" class="form-input w-28">
              <option v-for="n in 5" :key="n" :value="n">{{ n }} ★</option>
            </select>
            <button type="button" @click="removeItem(i)" class="text-gray-600 hover:text-red-400 text-sm">Remove</button>
          </div>
        </div>
        <p v-if="form.items.length === 0" class="text-xs text-gray-600 italic">No reviews yet — add your first one.</p>
      </div>

      <!-- FAQ -->
      <div v-if="type === 'faq'" class="card p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-bold text-white text-sm">Questions</h2>
          <button type="button" @click="addItem" class="btn-secondary btn-sm">+ Add Question</button>
        </div>

        <div v-for="(item, i) in form.items" :key="i" class="p-3 bg-gray-950 rounded-xl border border-white/5 space-y-2">
          <input v-model="item.question" placeholder="Question" class="form-input" />
          <textarea v-model="item.answer" rows="2" placeholder="Answer" class="form-input resize-none"></textarea>
          <div class="text-right">
            <button type="button" @click="removeItem(i)" class="text-gray-600 hover:text-red-400 text-sm">Remove</button>
          </div>
        </div>
        <p v-if="form.items.length === 0" class="text-xs text-gray-600 italic">No questions yet — add your first one.</p>
      </div>

      <!-- Category Showcase -->
      <div v-if="type === 'category_showcase'" class="card p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-bold text-white text-sm">Categories</h2>
          <button type="button" @click="addItem" class="btn-secondary btn-sm">+ Add Category</button>
        </div>

        <div v-for="(item, i) in form.items" :key="i" class="p-3 bg-gray-950 rounded-xl border border-white/5 flex gap-3 items-start">
          <div class="w-16 h-16 rounded-lg overflow-hidden bg-black/20 border border-white/10 shrink-0 relative">
            <img v-if="itemPreviews[i]" :src="itemPreviews[i]" class="w-full h-full object-cover" />
            <label class="absolute inset-0 flex items-center justify-center text-[10px] text-gray-500 cursor-pointer hover:bg-black/40">
              <span v-if="!itemPreviews[i]">+ Image</span>
              <input type="file" accept="image/*" class="hidden" @change="e => handleItemImage(e, i)" />
            </label>
          </div>
          <div class="flex-1 space-y-2">
            <input v-model="item.label" placeholder="Display label, e.g. Skincare" class="form-input" />
            <input v-model="item.category" placeholder="Matches product category, e.g. SERUM" class="form-input" />
          </div>
          <button type="button" @click="removeItem(i)" class="text-gray-600 hover:text-red-400 text-xl px-1">×</button>
        </div>
        <p v-if="form.items.length === 0" class="text-xs text-gray-600 italic">No categories yet — add your first one.</p>
        <p class="text-[11px] text-gray-500">The "matches category" value must exactly match how you've tagged products in Products → Category.</p>
      </div>

      <!-- Submit -->
      <div class="flex flex-col sm:flex-row gap-3">
        <button type="submit" :disabled="form.processing" class="btn-primary flex-1 sm:flex-none sm:px-8 py-3">
          {{ form.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Add Section') }}
        </button>
        <Link :href="route('merchant.sections.index')" class="btn-secondary text-center py-3 sm:px-8">
          Cancel
        </Link>
      </div>
    </form>
  </MerchantLayout>
</template>
