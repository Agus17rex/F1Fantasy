<template>
  <div class="rounded-full overflow-hidden flex items-center justify-center flex-shrink-0 relative"
    :class="claseSize"
    :style="{ backgroundColor: color + '22', borderLeft: '3px solid ' + color }"
    :title="`${piloto.nombre} ${piloto.apellido}`">
    <img v-if="piloto.foto && !error"
      :src="piloto.foto"
      :alt="piloto.apellido"
      class="w-full h-full object-cover object-top"
      @error="error = true" />
    <span v-else class="font-bold text-white tabular-nums" :class="claseTexto">
      {{ piloto.codigo || iniciales }}
    </span>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  piloto: { type: Object, required: true },
  size:   { type: String, default: 'md' },  // xs | sm | md | lg | xl
})

const error = ref(false)

const color = computed(() => props.piloto.escuderia?.color || '#666')

const iniciales = computed(() => {
  const f = props.piloto.nombre?.charAt(0) || ''
  const l = props.piloto.apellido?.charAt(0) || ''
  return (f + l).toUpperCase()
})

const claseSize = computed(() => ({
  xs: 'w-6 h-6',
  sm: 'w-8 h-8',
  md: 'w-10 h-10',
  lg: 'w-12 h-12',
  xl: 'w-20 h-20',
}[props.size] || 'w-10 h-10'))

const claseTexto = computed(() => ({
  xs: 'text-[9px]',
  sm: 'text-[10px]',
  md: 'text-xs',
  lg: 'text-sm',
  xl: 'text-xl',
}[props.size] || 'text-xs'))
</script>
