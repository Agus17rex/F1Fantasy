<template>
  <div class="rounded-md overflow-hidden flex items-center justify-center flex-shrink-0 bg-zinc-800"
    :class="claseSize"
    :style="coche.escuderia ? { borderLeft: '3px solid ' + coche.escuderia.color } : {}"
    :title="coche.nombre">
    <img v-if="coche.foto && !error"
      :src="coche.foto"
      :alt="coche.nombre"
      class="w-full h-full object-cover object-center"
      @error="error = true" />
    <span v-else class="text-white" :class="claseTexto">🏎️</span>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  coche: { type: Object, required: true },
  size:  { type: String, default: 'md' },
})

const error = ref(false)

const claseSize = computed(() => ({
  xs: 'w-6 h-6',
  sm: 'w-8 h-8',
  md: 'w-10 h-10',
  lg: 'w-12 h-12',
  xl: 'w-20 h-20',
}[props.size] || 'w-10 h-10'))

const claseTexto = computed(() => ({
  xs: 'text-xs',
  sm: 'text-sm',
  md: 'text-base',
  lg: 'text-lg',
  xl: 'text-2xl',
}[props.size] || 'text-base'))
</script>
