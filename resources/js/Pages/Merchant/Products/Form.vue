<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({ product: Object })

const isEditing = computed(() => !!props.product)

const form = useForm({
  name: props.product?.name ?? '',
  description: props.product?.description ?? '',
  price: props.product?.price ?? '',
  compare_at_price: props.product?.compare_at_price ?? '',
  stock_quantity: props.product?.stock_quantity ?? 0,
  sku: props.product?.sku ?? '',
  category: props.product?.category ?? '',
  is_active: props.product?.is_active ?? true,
  existing_images: props.product?.images ?? [],
  new_images: [],
  variants: props.product?.variants?.map(v => ({
    id: v.id,
    option_name: v.option_name,
    option_value: v.option_value,
    price_override: v.price_override ?? '',
    stock_quantity: v.stock_quantity,
  })) ?? [],
})

const imagePreviews = ref(props.product?.images?.map(p => `/storage/${p}`) ?? [])

function handleImages(e) {
  const files = Array.from(e.target.files)
  form.new_images = files
  files.forEach(file => {
    const reader = new FileReader()
    reader.onload = (ev) => imagePreviews.value.push(ev.target.result)
    reader.readAsDataURL(file)
  })
}

function removeExistingImage(index) {
  form.existing_images.splice(index, 1)
  imagePreviews.value.splice(index, 1)
}

function addVariant() {
  form.variants.push({ id: null, option_name: '', option_value: '', price_override: '', stock_quantity: 0 })
}

function removeVariant(index) {
  form.variants.splice(index, 1)
}

function submit() {
  if (isEditing.value) {
    form.transform((data) => ({
      ...data,
      _method: 'put'
    })).post(route('merchant.products.update', props.product))
  } else {
    form.post(route('merchant.products.store'))
  }
}
</script>

<template>
  <MerchantLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('merchant.products.index')" class="text-gray-500 hover:text-white text-sm transition-colors shrink-0">
          ← Back
        </Link>
        <h1 class="text-base font-semibold text-white truncate">{{ isEditing ? 'Edit Product' : 'Add Product' }}</h1>
      </div>
    </template>

    <form @submit.prevent="submit" enctype="multipart/form-data" class="max-w-3xl space-y-4">
      
      <!-- Basic Info -->
      <div class="card p-4 sm:p-6 space-y-4">
        <h2 class="font-bold text-white text-sm">Product Details</h2>

        <div>
          <label class="form-label">Product Name *</label>
          <input v-model="form.name" type="text" placeholder="e.g. Floral Lawn Suit" class="form-input" />
          <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="form-label">Description</label>
          <textarea v-model="form.description" rows="3" placeholder="Tell customers about this product..."
            class="form-input resize-none"></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Price (Rs.) *</label>
            <input v-model="form.price" type="number" min="0" step="0.01" placeholder="2500" class="form-input" />
            <p v-if="form.errors.price" class="form-error">{{ form.errors.price }}</p>
          </div>
          <div>
            <label class="form-label">Compare-at Price <span class="text-gray-600 font-normal">(for sale badge)</span></label>
            <input v-model="form.compare_at_price" type="number" min="0" step="0.01" placeholder="3500" class="form-input" />
          </div>
          <div>
            <label class="form-label">Stock Quantity *</label>
            <input v-model="form.stock_quantity" type="number" min="0" class="form-input" />
          </div>
          <div>
            <label class="form-label">SKU <span class="text-gray-600 font-normal">optional</span></label>
            <input v-model="form.sku" type="text" placeholder="SKU-001" class="form-input" />
          </div>
          <div class="sm:col-span-2">
            <label class="form-label">Category</label>
            <input v-model="form.category" type="text" placeholder="Lawn Suits" class="form-input" />
          </div>
          <div class="sm:col-span-2 flex items-center gap-3">
            <input v-model="form.is_active" id="is_active" type="checkbox" class="w-4 h-4 accent-emerald-500" />
            <label for="is_active" class="text-sm text-gray-300 cursor-pointer">List this product publicly</label>
          </div>
        </div>
      </div>

      <!-- Product Images -->
      <div class="card p-4 sm:p-6 space-y-4">
        <h2 class="font-bold text-white text-sm">
          Product Images <span class="text-gray-500 font-normal">(up to 5, max 3MB each)</span>
        </h2>

        <div class="flex flex-wrap gap-3">
          <div v-for="(preview, i) in imagePreviews" :key="i"
            class="relative w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden border border-white/10">
            <img :src="preview" class="w-full h-full object-cover" />
            <button type="button" @click="removeExistingImage(i)"
              class="absolute top-1 right-1 w-5 h-5 bg-black/70 text-white text-xs rounded-full flex items-center justify-center hover:bg-red-500 transition-colors">
              ×
            </button>
          </div>
          <label v-if="imagePreviews.length < 5"
            class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl border border-dashed border-white/20 flex flex-col items-center justify-center text-gray-600 cursor-pointer hover:border-white/40 hover:text-gray-400 transition-all">
            <span class="text-2xl mb-1">+</span>
            <span class="text-xs">Add</span>
            <input type="file" accept="image/*" multiple class="hidden" @change="handleImages" />
          </label>
        </div>
        <p v-if="form.errors.new_images" class="form-error">{{ form.errors.new_images }}</p>
      </div>

      <!-- Variants -->
      <div class="card p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="font-bold text-white text-sm">Variants</h2>
            <p class="text-xs text-gray-500 mt-0.5">Add size/color/material options.</p>
          </div>
          <button type="button" @click="addVariant" class="btn-secondary btn-sm shrink-0">
            + Add Variant
          </button>
        </div>

        <div v-if="form.variants.length === 0" class="text-xs text-gray-600 italic">
          No variants. All customers see the same price & stock.
        </div>

        <div v-for="(variant, i) in form.variants" :key="i"
          class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-gray-950 rounded-xl border border-white/5">
          <div>
            <label class="form-label text-[10px]">Option (e.g. Size)</label>
            <input v-model="variant.option_name" type="text" placeholder="Size" class="form-input" />
          </div>
          <div>
            <label class="form-label text-[10px]">Value (e.g. Large)</label>
            <input v-model="variant.option_value" type="text" placeholder="Large" class="form-input" />
          </div>
          <div>
            <label class="form-label text-[10px]">Price Override</label>
            <input v-model="variant.price_override" type="number" min="0" placeholder="Same" class="form-input" />
          </div>
          <div class="flex items-end gap-2">
            <div class="flex-1">
              <label class="form-label text-[10px]">Stock</label>
              <input v-model="variant.stock_quantity" type="number" min="0" class="form-input" />
            </div>
            <button type="button" @click="removeVariant(i)"
              class="mb-0.5 text-gray-600 hover:text-red-400 text-xl transition-colors pb-2">×</button>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex flex-col sm:flex-row gap-3">
        <button type="submit" :disabled="form.processing" class="btn-primary flex-1 sm:flex-none sm:px-8 py-3">
          {{ form.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Add Product') }}
        </button>
        <Link :href="route('merchant.products.index')" class="btn-secondary text-center py-3 sm:px-8">
          Cancel
        </Link>
      </div>
    </form>
  </MerchantLayout>
</template>
