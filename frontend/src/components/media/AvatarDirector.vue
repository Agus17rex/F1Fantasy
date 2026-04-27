<template>
  <div class="rounded-full overflow-hidden flex items-center justify-center flex-shrink-0 bg-zinc-800"
    :class="claseSize"
    :style="director.escuderia ? { borderLeft: '3px solid ' + director.escuderia.color } : {}"
    :title="director.nombre">
    <img v-if="director.foto && !error"
      :src="director.foto"
      :alt="director.nombre"
      class="w-full h-full object-cover object-top"
      @error="error = true" />
    <span v-else class="text-white" :class="claseTexto">🎩</span>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  director: { type: Object, required: true },
  size:     { type: String, default: 'md' },
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
