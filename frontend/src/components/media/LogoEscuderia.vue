<template>
  <div v-if="!escuderia" :class="claseSize" class="rounded-full bg-zinc-700 flex-shrink-0"></div>
  <div v-else
    class="rounded-md overflow-hidden flex items-center justify-center flex-shrink-0"
    :class="claseSize"
    :style="{ backgroundColor: 'white' }"
    :title="escuderia.nombre">
    <img v-if="escuderia.logo && !error"
      :src="escuderia.logo"
      :alt="escuderia.nombre"
      class="w-full h-full object-contain p-0.5"
      @error="error = true" />
    <div v-else class="w-full h-full rounded-md"
      :style="{ backgroundColor: escuderia.color || '#888' }"></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  escuderia: { type: Object, default: null },
  size:      { type: String, default: 'md' },
})

const error = ref(false)

const claseSize = computed(() => ({
  xs: 'w-3 h-3',
  sm: 'w-6 h-6',
  md: 'w-8 h-8',
  lg: 'w-12 h-12',
  xl: 'w-20 h-20',
}[props.size] || 'w-8 h-8'))
</script>
