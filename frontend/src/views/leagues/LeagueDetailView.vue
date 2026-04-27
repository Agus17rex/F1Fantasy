<template>
  <div class="space-y-4">

    <!-- ── Header de la liga ─────────────────────────────────────────────── -->
    <div v-if="liga" class="card border-l-4 border-l-red-500">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h2 class="text-2xl font-black text-white">{{ liga.nombre }}</h2>
          <p v-if="liga.descripcion" class="text-zinc-400 text-sm mt-0.5">{{ liga.descripcion }}</p>
          <div class="flex items-center gap-3 mt-2 flex-wrap">
            <span class="text-zinc-500 text-xs">{{ liga.miembros_count }} miembros</span>
            <span class="text-xs bg-zinc-800 text-zinc-400 px-2 py-0.5 rounded font-mono">{{ liga.codigo }}</span>
            <span class="text-xs" :class="liga.es_privada ? 'text-amber-400' : 'text-green-400'">
              {{ liga.es_privada ? '🔒 Privada' : '🌐 Pública' }}
            </span>
            <span class="text-xs text-red-400 font-semibold">
              💰 {{ M(liga.presupuesto_inicial) }} por equipo
            </span>
          </div>
        </div>
        <!-- Presupuesto restante del usuario -->
        <div v-if="presupuestoRestante !== null" class="text-right flex-shrink-0">
          <p class="text-2xl font-black" :class="presupuestoRestante < 5_000_000 ? 'text-red-400' : 'text-white'">
            {{ M(presupuestoRestante) }}
          </p>
          <p class="text-zinc-500 text-xs">presupuesto restante</p>
        </div>
      </div>
    </div>

    <!-- ── Tabs ──────────────────────────────────────────────────────────── -->
    <div class="flex gap-1 bg-zinc-900 rounded-xl p-1">
      <button v-for="tab in TABS" :key="tab.id"
        @click="tabActiva = tab.id"
        :class="tabActiva === tab.id
          ? 'bg-zinc-700 text-white shadow'
          : 'text-zinc-400 hover:text-white'"
        class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
      >
        {{ tab.icono }} {{ tab.label }}
      </button>
    </div>

    <!-- ── TAB: Clasificación ────────────────────────────────────────────── -->
    <div v-if="tabActiva === 'clasificacion'">
      <div v-if="cargandoLiga" class="text-zinc-400 text-center py-10">Cargando...</div>
      <div v-else-if="clasificacion.length === 0" class="card text-center py-10">
        <p class="text-zinc-500">Aún no hay puntuaciones en esta liga</p>
      </div>
      <div v-else class="card divide-y divide-zinc-800">
        <div
          v-for="entrada in clasificacion"
          :key="entrada.usuario.id"
          class="flex items-center gap-4 py-3 first:pt-0 last:pb-0 cursor-pointer hover:bg-zinc-800/40 -mx-5 px-5 rounded-lg transition-colors"
          :class="entrada.usuario.id === authStore.user?.id ? 'bg-red-500/5' : ''"
          @click="verEquipoUsuario(entrada.usuario)"
        >
          <!-- Posición -->
          <div class="w-8 text-center flex-shrink-0">
            <span v-if="entrada.posicion === 1" class="text-xl">🥇</span>
            <span v-else-if="entrada.posicion === 2" class="text-xl">🥈</span>
            <span v-else-if="entrada.posicion === 3" class="text-xl">🥉</span>
            <span v-else class="text-zinc-500 text-sm font-mono font-bold">{{ entrada.posicion }}</span>
          </div>
          <!-- Avatar -->
          <div class="w-9 h-9 rounded-full bg-red-600/80 flex items-center justify-center text-sm font-bold flex-shrink-0">
            {{ entrada.usuario.nombre.charAt(0).toUpperCase() }}
          </div>
          <!-- Nombre -->
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-white text-sm truncate">
              {{ entrada.usuario.nombre }}
              <span v-if="entrada.usuario.id === authStore.user?.id" class="text-red-400 text-xs ml-1">(tú)</span>
            </p>
            <p class="text-zinc-500 text-xs">@{{ entrada.usuario.usuario }}</p>
          </div>
          <!-- Puntos + icono -->
          <div class="text-right flex-shrink-0 flex items-center gap-3">
            <div>
              <p class="font-black text-white text-lg">{{ entrada.total_puntos }}</p>
              <p class="text-zinc-500 text-xs">pts</p>
            </div>
            <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- ── TAB: Mercado ──────────────────────────────────────────────────── -->
    <div v-if="tabActiva === 'mercado'" class="space-y-3">
      <div v-if="cargandoMercado" class="text-zinc-400 text-center py-10">Cargando mercado...</div>
      <template v-else>
        <!-- Sub-tabs del mercado -->
        <div class="flex gap-1 bg-zinc-900/60 rounded-lg p-1">
          <button v-for="cat in CATEGORIAS" :key="cat.id"
            @click="categoriaActiva = cat.id"
            :class="categoriaActiva === cat.id ? 'bg-zinc-700 text-white' : 'text-zinc-400 hover:text-white'"
            class="flex-1 text-xs py-1.5 rounded-md transition-colors font-medium"
          >
            {{ cat.label }}
            <span class="opacity-50">({{ mercado[cat.id]?.length || 0 }})</span>
          </button>
        </div>

        <!-- Buscador -->
        <input v-model="busqueda" type="text" class="input" :placeholder="`Buscar ${categoriaActiva}...`" />

        <!-- Lista: Pilotos -->
        <div v-if="categoriaActiva === 'pilotos'" class="space-y-1.5">
          <div v-for="piloto in pilotosFiltrados" :key="piloto.id"
            class="card !p-3 flex items-center gap-3"
          >
            <AvatarPiloto :piloto="piloto" size="md" />
            <div class="flex-1 min-w-0">
              <p class="text-white text-sm font-semibold">{{ piloto.nombre }} <strong>{{ piloto.apellido }}</strong></p>
              <p class="text-zinc-500 text-xs flex items-center gap-1.5">
                <LogoEscuderia :escuderia="piloto.escuderia" size="xs" />
                {{ piloto.escuderia?.nombre || '—' }}
              </p>
            </div>
            <p class="text-red-400 font-bold text-sm flex-shrink-0">{{ M(piloto.precio) }}</p>
            <!-- Botones compra/venta/robo -->
            <div class="flex-shrink-0">
              <template v-if="piloto.en_equipo">
                <span class="text-xs text-green-400 mr-2">✓ En equipo</span>
                <button @click="vender('piloto', piloto)"
                  class="text-xs bg-zinc-800 hover:bg-red-600/20 hover:text-red-400 text-zinc-400 border border-zinc-700 hover:border-red-500/50 px-2 py-1 rounded transition-colors">
                  Vender
                </button>
              </template>
              <template v-else-if="piloto.en_equipo_ajeno">
                <div class="flex flex-col items-end gap-1">
                  <span class="text-xs text-zinc-500">{{ piloto.propietario?.nombre }}</span>
                  <span v-if="piloto.protegido"
                    class="text-xs bg-zinc-800 text-zinc-500 border border-zinc-700 px-2 py-1 rounded">
                    🛡️ {{ piloto.dias_proteccion }}d
                  </span>
                  <button v-else @click="robar('piloto', piloto)"
                    :disabled="!puedoComprar(piloto.precio)"
                    class="text-xs bg-orange-600 hover:bg-orange-500 disabled:bg-zinc-800 disabled:text-zinc-600 text-white px-3 py-1 rounded transition-colors">
                    🔄 Robar
                  </button>
                </div>
              </template>
              <template v-else>
                <button @click="comprarPiloto(piloto)"
                  :disabled="!puedoComprar(piloto.precio)"
                  class="text-xs bg-red-600 hover:bg-red-500 disabled:bg-zinc-800 disabled:text-zinc-600 text-white px-3 py-1 rounded transition-colors">
                  Comprar
                </button>
              </template>
            </div>
          </div>
          <p v-if="pilotosFiltrados.length === 0" class="text-zinc-500 text-center py-6">Sin resultados</p>
        </div>

        <!-- Lista: Escuderías -->
        <div v-if="categoriaActiva === 'escuderias'" class="space-y-1.5">
          <div v-for="escuderia in escuderiasFiltradas" :key="escuderia.id"
            class="card !p-3 flex items-center gap-3"
          >
            <LogoEscuderia :escuderia="escuderia" size="md" />
            <div class="flex-1 min-w-0">
              <p class="text-white text-sm font-semibold">{{ escuderia.nombre }}</p>
              <p class="text-zinc-500 text-xs">{{ escuderia.nacionalidad }}</p>
            </div>
            <p class="text-red-400 font-bold text-sm flex-shrink-0">{{ M(escuderia.precio) }}</p>
            <div class="flex-shrink-0">
              <template v-if="escuderia.en_equipo">
                <span class="text-xs text-green-400 mr-2">✓ En equipo</span>
                <button @click="vender('escuderia', escuderia)"
                  class="text-xs bg-zinc-800 hover:bg-red-600/20 hover:text-red-400 text-zinc-400 border border-zinc-700 hover:border-red-500/50 px-2 py-1 rounded transition-colors">
                  Vender
                </button>
              </template>
              <template v-else-if="escuderia.en_equipo_ajeno">
                <div class="flex flex-col items-end gap-1">
                  <span class="text-xs text-zinc-500">{{ escuderia.propietario?.nombre }}</span>
                  <span v-if="escuderia.protegido"
                    class="text-xs bg-zinc-800 text-zinc-500 border border-zinc-700 px-2 py-1 rounded">
                    🛡️ {{ escuderia.dias_proteccion }}d
                  </span>
                  <button v-else @click="robar('escuderia', escuderia)"
                    :disabled="!puedoComprar(escuderia.precio)"
                    class="text-xs bg-orange-600 hover:bg-orange-500 disabled:bg-zinc-800 disabled:text-zinc-600 text-white px-3 py-1 rounded transition-colors">
                    🔄 Robar
                  </button>
                </div>
              </template>
              <button v-else @click="comprar('escuderia', escuderia)"
                :disabled="!puedoComprar(escuderia.precio)"
                class="text-xs bg-red-600 hover:bg-red-500 disabled:bg-zinc-800 disabled:text-zinc-600 text-white px-3 py-1 rounded transition-colors">
                Comprar
              </button>
            </div>
          </div>
          <p v-if="escuderiasFiltradas.length === 0" class="text-zinc-500 text-center py-6">Sin resultados</p>
        </div>

        <!-- Lista: Directores -->
        <div v-if="categoriaActiva === 'directores'" class="space-y-1.5">
          <div v-for="director in directoresFiltrados" :key="director.id"
            class="card !p-3 flex items-center gap-3"
          >
            <AvatarDirector :director="director" size="md" />
            <div class="flex-1 min-w-0">
              <p class="text-white text-sm font-semibold">{{ director.nombre }}</p>
              <p class="text-zinc-500 text-xs flex items-center gap-1.5">
                <LogoEscuderia :escuderia="director.escuderia" size="xs" />
                {{ director.escuderia?.nombre || '—' }} · {{ director.nacionalidad }}
              </p>
            </div>
            <p class="text-red-400 font-bold text-sm flex-shrink-0">{{ M(director.precio) }}</p>
            <div class="flex-shrink-0">
              <template v-if="director.en_equipo">
                <span class="text-xs text-green-400 mr-2">✓ Director</span>
                <button @click="vender('director', director)"
                  class="text-xs bg-zinc-800 hover:bg-red-600/20 hover:text-red-400 text-zinc-400 border border-zinc-700 hover:border-red-500/50 px-2 py-1 rounded transition-colors">
                  Vender
                </button>
              </template>
              <template v-else-if="director.en_equipo_ajeno">
                <div class="flex flex-col items-end gap-1">
                  <span class="text-xs text-zinc-500">{{ director.propietario?.nombre }}</span>
                  <span v-if="director.protegido"
                    class="text-xs bg-zinc-800 text-zinc-500 border border-zinc-700 px-2 py-1 rounded">
                    🛡️ {{ director.dias_proteccion }}d
                  </span>
                  <button v-else @click="robar('director', director)"
                    :disabled="!puedoComprar(director.precio)"
                    class="text-xs bg-orange-600 hover:bg-orange-500 disabled:bg-zinc-800 disabled:text-zinc-600 text-white px-3 py-1 rounded transition-colors">
                    🔄 Robar
                  </button>
                </div>
              </template>
              <button v-else @click="comprar('director', director)"
                :disabled="!puedoComprar(director.precio)"
                class="text-xs bg-red-600 hover:bg-red-500 disabled:bg-zinc-800 disabled:text-zinc-600 text-white px-3 py-1 rounded transition-colors">
                Comprar
              </button>
            </div>
          </div>
          <p v-if="directoresFiltrados.length === 0" class="text-zinc-500 text-center py-6">Sin resultados</p>
        </div>
      </template>
    </div>

    <!-- ── TAB: Mi Equipo ────────────────────────────────────────────────── -->
    <div v-if="tabActiva === 'equipo'" class="space-y-3">
      <div v-if="cargandoEquipo" class="text-zinc-400 text-center py-10">Cargando equipo...</div>
      <template v-else-if="equipo">

        <!-- Estado del equipo -->
        <div class="flex items-center justify-between">
          <span :class="equipo.es_valido ? 'text-green-400' : 'text-amber-400'" class="text-sm font-medium">
            {{ equipo.es_valido ? '✓ Equipo válido — listo para puntuar' : '⚠ Equipo incompleto' }}
          </span>
          <p class="text-zinc-500 text-xs">
            Completa: 3 pilotos · 1 director · 1 escudería
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <!-- Pilotos -->
          <SlotEquipoCard titulo="🏁 Pilotos" subtitulo="todos puntúan" :max="3">
            <FilaPiloto v-for="p in pilotos" :key="p.id" :piloto="p"
              @vender="venderDesdeEquipo('piloto', p)" />
            <SlotVacio v-for="n in (3 - pilotos.length)" :key="'p'+n" />
          </SlotEquipoCard>

          <!-- Director -->
          <SlotEquipoCard titulo="🎩 Director" subtitulo="puntos del equipo ÷ 2" :max="1">
            <div v-for="d in directores" :key="d.id"
              class="flex items-center gap-2 p-2 rounded-lg bg-zinc-800/50">
              <AvatarDirector :director="d" size="sm" />
              <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate">{{ d.nombre }}</p>
                <p class="text-zinc-500 text-xs">{{ d.escuderia?.nombre }} · {{ M(d.precio) }}</p>
              </div>
              <button @click="venderDesdeEquipo('director', d)"
                class="text-zinc-600 hover:text-red-400 text-xs px-1 transition-colors">✕</button>
            </div>
            <SlotVacio v-if="directores.length === 0" />
          </SlotEquipoCard>

          <!-- Escudería -->
          <SlotEquipoCard titulo="🏎️ Escudería" subtitulo="suma puntos de sus 2 pilotos" :max="1">
            <div v-for="e in escuderias" :key="e.id"
              class="flex items-center gap-2 p-2 rounded-lg bg-zinc-800/50">
              <LogoEscuderia :escuderia="e" size="sm" />
              <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium">{{ e.nombre }}</p>
                <p class="text-zinc-500 text-xs">{{ M(e.precio) }}</p>
              </div>
              <button @click="venderDesdeEquipo('escuderia', e)"
                class="text-zinc-600 hover:text-red-400 text-xs px-1 transition-colors">✕</button>
            </div>
            <SlotVacio v-if="escuderias.length === 0" />
          </SlotEquipoCard>
        </div>

        <p class="text-zinc-600 text-xs text-center">
          Para comprar o vender ve al mercado
        </p>
      </template>
    </div>

    <!-- ── TAB: Puntuaciones ─────────────────────────────────────────────── -->
    <div v-if="tabActiva === 'puntos'" class="space-y-3">
      <div v-if="cargandoPuntuaciones" class="text-zinc-400 text-center py-10">Cargando...</div>
      <div v-else-if="puntuaciones.length === 0" class="card text-center py-10 space-y-2">
        <p class="text-4xl">🏁</p>
        <p class="text-zinc-400 font-medium">Todavía no hay carreras puntuadas</p>
        <p class="text-zinc-600 text-xs">Los puntos aparecerán aquí tras cada gran premio</p>
      </div>
      <template v-else>
        <!-- Resumen total -->
        <div class="card flex items-center justify-between">
          <p class="text-zinc-400 text-sm">Total acumulado</p>
          <p class="text-3xl font-black text-white">
            {{ puntuaciones.reduce((s, p) => s + p.puntos_total, 0) }}
            <span class="text-zinc-500 text-base font-normal ml-1">pts</span>
          </p>
        </div>

        <!-- Una card por carrera -->
        <div v-for="p in puntuaciones" :key="p.carrera_id" class="card">
          <!-- Cabecera clicable -->
          <div class="flex items-center justify-between cursor-pointer select-none"
            @click="carreraAbierta = carreraAbierta === p.carrera_id ? null : p.carrera_id">
            <div>
              <p class="font-bold text-white">{{ p.carrera_nombre }}</p>
              <p class="text-zinc-500 text-xs">{{ p.carrera_fecha ? formatFecha(p.carrera_fecha) : '—' }}</p>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-right">
                <span class="text-2xl font-black"
                  :class="p.puntos_total > 0 ? 'text-green-400' : p.puntos_total < 0 ? 'text-red-400' : 'text-zinc-400'">
                  {{ p.puntos_total > 0 ? '+' : '' }}{{ p.puntos_total }}
                </span>
                <span class="text-zinc-500 text-xs ml-1">pts</span>
              </div>
              <svg class="w-4 h-4 text-zinc-500 transition-transform duration-200 flex-shrink-0"
                :class="carreraAbierta === p.carrera_id ? 'rotate-90' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </div>

          <!-- Desglose expandible -->
          <div v-if="carreraAbierta === p.carrera_id"
            class="mt-4 pt-4 border-t border-zinc-800 space-y-1">

            <!-- Pilotos -->
            <p class="text-zinc-600 text-xs font-semibold uppercase tracking-wider mb-2">Pilotos</p>
            <div v-for="piloto in p.desglose.pilotos" :key="piloto.id"
              class="flex items-center gap-2 py-1">
              <div class="w-1 h-6 rounded-full flex-shrink-0"
                :style="{ backgroundColor: piloto.color || '#555' }"></div>
              <span class="text-zinc-300 text-sm flex-1">{{ piloto.nombre }}</span>
              <span class="font-bold text-sm tabular-nums"
                :class="piloto.total > 0 ? 'text-green-400' : piloto.total < 0 ? 'text-red-400' : 'text-zinc-600'">
                {{ piloto.total > 0 ? '+' : '' }}{{ piloto.total }}
              </span>
            </div>
            <p v-if="!p.desglose.pilotos.length" class="text-zinc-700 text-sm">—</p>

            <!-- Escudería -->
            <p class="text-zinc-600 text-xs font-semibold uppercase tracking-wider mt-3 mb-2">Escudería</p>
            <div v-for="esc in p.desglose.escuderias" :key="esc.id"
              class="flex items-center gap-2 py-1">
              <div class="w-3 h-3 rounded-full flex-shrink-0"
                :style="{ backgroundColor: esc.color || '#555' }"></div>
              <span class="text-zinc-300 text-sm flex-1">{{ esc.nombre }}</span>
              <span class="font-bold text-sm tabular-nums"
                :class="esc.total > 0 ? 'text-green-400' : esc.total < 0 ? 'text-red-400' : 'text-zinc-600'">
                {{ esc.total > 0 ? '+' : '' }}{{ esc.total }}
              </span>
            </div>
            <p v-if="!p.desglose.escuderias.length" class="text-zinc-700 text-sm">—</p>

            <!-- Director -->
            <p class="text-zinc-600 text-xs font-semibold uppercase tracking-wider mt-3 mb-2">Director</p>
            <div v-for="dir in p.desglose.directores" :key="dir.id"
              class="flex items-center gap-2 py-1">
              <span class="text-base flex-shrink-0">🎩</span>
              <div class="flex-1 min-w-0">
                <span class="text-zinc-300 text-sm">{{ dir.nombre }}</span>
                <span v-if="dir.escuderia" class="text-zinc-600 text-xs ml-1">· {{ dir.escuderia }}</span>
              </div>
              <span class="font-bold text-sm tabular-nums"
                :class="dir.total > 0 ? 'text-green-400' : dir.total < 0 ? 'text-red-400' : 'text-zinc-600'">
                {{ dir.total > 0 ? '+' : '' }}{{ dir.total }}
              </span>
            </div>
            <p v-if="!p.desglose.directores.length" class="text-zinc-700 text-sm">—</p>
          </div>
        </div>
      </template>
    </div>

    <!-- ── Modal: equipo de otro usuario ──────────────────────────────── -->
    <Teleport to="body">
      <div v-if="modalEquipoAjeno"
        class="fixed inset-0 bg-black/70 z-50 flex items-start justify-center overflow-y-auto p-4"
        @click.self="modalEquipoAjeno = null">
        <div class="card w-full max-w-lg mx-auto mt-10 mb-10">

          <!-- Cabecera -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-red-600/80 flex items-center justify-center font-bold text-white flex-shrink-0">
                {{ modalEquipoAjeno.usuario?.nombre?.charAt(0).toUpperCase() }}
              </div>
              <div>
                <p class="font-bold text-white">{{ modalEquipoAjeno.usuario?.nombre }}</p>
                <p class="text-zinc-500 text-xs">@{{ modalEquipoAjeno.usuario?.usuario }}</p>
              </div>
            </div>
            <button @click="modalEquipoAjeno = null"
              class="text-zinc-500 hover:text-white transition-colors text-lg">✕</button>
          </div>

          <div v-if="cargandoEquipoAjeno" class="text-zinc-400 text-center py-8">Cargando equipo...</div>

          <template v-else-if="equipoAjeno">
            <!-- Pilotos -->
            <div class="mb-3">
              <p class="text-zinc-500 text-xs font-semibold uppercase tracking-wider mb-2">🏁 Pilotos</p>
              <div class="space-y-1.5">
                <div v-for="p in (equipoAjeno.pilotos || [])" :key="p.id"
                  class="flex items-center gap-2 p-2 rounded-lg bg-zinc-800/50">
                  <AvatarPiloto :piloto="p" size="sm" />
                  <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium truncate">{{ p.nombre }} {{ p.apellido }}</p>
                    <p class="text-zinc-500 text-xs">{{ p.escuderia?.nombre || '—' }}</p>
                  </div>
                  <span class="text-red-400 text-xs font-mono font-semibold">{{ M(p.precio) }}</span>
                  <span v-if="p.pivot?.fecha_seleccion && diasProteccionRestantes(p.pivot.fecha_seleccion) > 0"
                    class="text-xs bg-zinc-700 text-zinc-400 px-1.5 py-0.5 rounded">
                    🛡️ {{ diasProteccionRestantes(p.pivot.fecha_seleccion) }}d
                  </span>
                </div>
                <p v-if="!equipoAjeno.pilotos?.length"
                  class="text-zinc-700 text-sm text-center py-1">Sin pilotos</p>
              </div>
            </div>

            <!-- Director -->
            <div class="mb-3">
              <p class="text-zinc-500 text-xs font-semibold uppercase tracking-wider mb-2">🎩 Director</p>
              <div class="space-y-1.5">
                <div v-for="d in (equipoAjeno.directores || [])" :key="d.id"
                  class="flex items-center gap-2 p-2 rounded-lg bg-zinc-800/50">
                  <AvatarDirector :director="d" size="sm" />
                  <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium">{{ d.nombre }}</p>
                    <p class="text-zinc-500 text-xs">{{ d.escuderia?.nombre }}</p>
                  </div>
                  <span class="text-red-400 text-xs font-mono font-semibold">{{ M(d.precio) }}</span>
                  <span v-if="d.pivot?.fecha_seleccion && diasProteccionRestantes(d.pivot.fecha_seleccion) > 0"
                    class="text-xs bg-zinc-700 text-zinc-400 px-1.5 py-0.5 rounded">
                    🛡️ {{ diasProteccionRestantes(d.pivot.fecha_seleccion) }}d
                  </span>
                </div>
                <p v-if="!equipoAjeno.directores?.length"
                  class="text-zinc-700 text-sm text-center py-1">Sin director</p>
              </div>
            </div>

            <!-- Escudería -->
            <div>
              <p class="text-zinc-500 text-xs font-semibold uppercase tracking-wider mb-2">🏎️ Escudería</p>
              <div class="space-y-1.5">
                <div v-for="e in (equipoAjeno.escuderias || [])" :key="e.id"
                  class="flex items-center gap-2 p-2 rounded-lg bg-zinc-800/50">
                  <LogoEscuderia :escuderia="e" size="sm" />
                  <p class="flex-1 text-white text-sm font-medium">{{ e.nombre }}</p>
                  <span class="text-red-400 text-xs font-mono font-semibold">{{ M(e.precio) }}</span>
                  <span v-if="e.pivot?.fecha_seleccion && diasProteccionRestantes(e.pivot.fecha_seleccion) > 0"
                    class="text-xs bg-zinc-700 text-zinc-400 px-1.5 py-0.5 rounded">
                    🛡️ {{ diasProteccionRestantes(e.pivot.fecha_seleccion) }}d
                  </span>
                </div>
                <p v-if="!equipoAjeno.escuderias?.length"
                  class="text-zinc-700 text-sm text-center py-1">Sin escudería</p>
              </div>
            </div>
          </template>
        </div>
      </div>
    </Teleport>

    <!-- ── Toast ─────────────────────────────────────────────────────────── -->
    <div v-if="toast"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-zinc-800 text-white text-sm px-5 py-3 rounded-xl shadow-lg border border-zinc-700 z-50 whitespace-nowrap">
      {{ toast }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ligaService, equipoService } from '@/services/leagueService'
import AvatarPiloto    from '@/components/media/AvatarPiloto.vue'
import LogoEscuderia   from '@/components/media/LogoEscuderia.vue'
import AvatarDirector  from '@/components/media/AvatarDirector.vue'

const route     = useRoute()
const authStore = useAuthStore()
const ligaId    = route.params.id

// ─── Estado ───────────────────────────────────────────────────────────────────
const liga             = ref(null)
const clasificacion    = ref([])
const equipo           = ref(null)
const mercado          = ref({ pilotos: [], escuderias: [], directores: [] })
const presupuestoRestante = ref(null)
const puntuaciones     = ref([])
const carreraAbierta   = ref(null)

const cargandoLiga         = ref(false)
const cargandoMercado      = ref(false)
const cargandoEquipo       = ref(false)
const cargandoPuntuaciones = ref(false)

const tabActiva       = ref('clasificacion')
const categoriaActiva = ref('pilotos')
const busqueda        = ref('')
const toast           = ref('')
let   timerToast      = null

// ─── Estado: equipo de otro usuario ──────────────────────────────────────────
const modalEquipoAjeno    = ref(null)
const equipoAjeno         = ref(null)
const cargandoEquipoAjeno = ref(false)

const TABS = [
  { id: 'clasificacion', label: 'Clasificación', icono: '🏆' },
  { id: 'mercado',       label: 'Mercado',        icono: '🛒' },
  { id: 'equipo',        label: 'Mi Equipo',      icono: '👥' },
  { id: 'puntos',        label: 'Puntos',          icono: '📊' },
]

const CATEGORIAS = [
  { id: 'pilotos',    label: 'Pilotos' },
  { id: 'escuderias', label: 'Escuderías' },
  { id: 'directores', label: 'Directores' },
]

// ─── Datos del equipo ────────────────────────────────────────────────────────
const pilotos    = computed(() => equipo.value?.pilotos    || [])
const directores = computed(() => equipo.value?.directores || [])
const escuderias = computed(() => equipo.value?.escuderias || [])

// ─── Filtros del mercado ──────────────────────────────────────────────────────
const pilotosFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase()
  return (mercado.value.pilotos || []).filter(p =>
    !q ||
    p.nombre?.toLowerCase().includes(q) ||
    p.apellido?.toLowerCase().includes(q) ||
    p.escuderia?.nombre?.toLowerCase().includes(q)
  )
})

const escuderiasFiltradas = computed(() => {
  const q = busqueda.value.toLowerCase()
  return (mercado.value.escuderias || []).filter(e =>
    !q || e.nombre?.toLowerCase().includes(q)
  )
})

const directoresFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase()
  return (mercado.value.directores || []).filter(d =>
    !q ||
    d.nombre?.toLowerCase().includes(q) ||
    d.escuderia?.nombre?.toLowerCase().includes(q)
  )
})

// ─── Helpers ─────────────────────────────────────────────────────────────────
function M(v) { return v != null ? (v / 1_000_000).toFixed(1) + 'M' : '—' }
function puedoComprar(precio) { return presupuestoRestante.value != null && precio <= presupuestoRestante.value }

function formatFecha(fecha) {
  return new Date(fecha).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' })
}

function mostrarToast(msg) {
  toast.value = msg
  clearTimeout(timerToast)
  timerToast = setTimeout(() => (toast.value = ''), 3000)
}

// ─── Ver equipo de otro usuario ───────────────────────────────────────────────
function diasProteccionRestantes(fechaSeleccion) {
  if (!fechaSeleccion) return 0
  const diasPasados = Math.floor((Date.now() - new Date(fechaSeleccion).getTime()) / 86_400_000)
  return Math.max(0, 7 - diasPasados)
}

async function verEquipoUsuario(usuario) {
  modalEquipoAjeno.value    = { usuario }
  equipoAjeno.value         = null
  cargandoEquipoAjeno.value = true
  try {
    const { data } = await equipoService.getEquipoDeUsuario(ligaId, usuario.id)
    equipoAjeno.value = data.equipo
  } catch {
    mostrarToast('No se pudo cargar el equipo')
  } finally {
    cargandoEquipoAjeno.value = false
  }
}

// ─── Carga de datos ───────────────────────────────────────────────────────────
async function cargarLiga() {
  cargandoLiga.value = true
  try {
    const { data } = await ligaService.getLiga(ligaId)
    liga.value          = data.liga
    clasificacion.value = data.clasificacion
  } finally {
    cargandoLiga.value = false
  }
}

async function cargarMercado() {
  cargandoMercado.value = true
  try {
    const { data } = await ligaService.getMercado(ligaId)
    mercado.value             = data
    presupuestoRestante.value = data.presupuesto_restante
  } finally {
    cargandoMercado.value = false
  }
}

async function cargarEquipo() {
  cargandoEquipo.value = true
  try {
    const { data } = await equipoService.getEquipo(ligaId)
    equipo.value              = data.equipo
    presupuestoRestante.value = data.presupuesto_restante
  } finally {
    cargandoEquipo.value = false
  }
}

async function cargarPuntuaciones() {
  cargandoPuntuaciones.value = true
  try {
    const { data } = await ligaService.getPuntuaciones(ligaId)
    puntuaciones.value = data.puntuaciones
  } finally {
    cargandoPuntuaciones.value = false
  }
}

async function recargarTodo() {
  await Promise.all([cargarMercado(), cargarEquipo()])
}

// ─── Acciones de compra ───────────────────────────────────────────────────────
async function comprarPiloto(piloto) {
  try {
    const { data } = await equipoService.comprarPiloto(ligaId, piloto.id)
    mostrarToast(data.message)
    await recargarTodo()
  } catch (e) {
    mostrarToast(e.response?.data?.message || 'Error al comprar')
  }
}

async function comprar(tipo, item) {
  try {
    let res
    if (tipo === 'escuderia') res = await equipoService.comprarEscuderia(ligaId, item.id)
    if (tipo === 'director')  res = await equipoService.comprarDirector(ligaId, item.id)
    mostrarToast(res.data.message)
    await recargarTodo()
  } catch (e) {
    mostrarToast(e.response?.data?.message || 'Error al comprar')
  }
}

// ─── Acción de robo ───────────────────────────────────────────────────────────
async function robar(tipo, item) {
  try {
    let res
    if (tipo === 'piloto')    res = await equipoService.robarPiloto(ligaId, item.id)
    if (tipo === 'escuderia') res = await equipoService.robarEscuderia(ligaId, item.id)
    if (tipo === 'director')  res = await equipoService.robarDirector(ligaId, item.id)
    mostrarToast(res.data.message)
    await recargarTodo()
  } catch (e) {
    mostrarToast(e.response?.data?.message || 'Error al robar')
  }
}

// ─── Acciones de venta ────────────────────────────────────────────────────────
async function vender(tipo, item) {
  try {
    let res
    if (tipo === 'piloto')    res = await equipoService.venderPiloto(ligaId, item.id)
    if (tipo === 'escuderia') res = await equipoService.venderEscuderia(ligaId, item.id)
    if (tipo === 'director')  res = await equipoService.venderDirector(ligaId, item.id)
    mostrarToast(res.data.message)
    await recargarTodo()
  } catch (e) {
    mostrarToast(e.response?.data?.message || 'Error al vender')
  }
}

async function venderDesdeEquipo(tipo, item) {
  await vender(tipo, item)
}

// ─── Montaje ──────────────────────────────────────────────────────────────────
onMounted(async () => {
  await cargarLiga()
  await Promise.all([cargarMercado(), cargarEquipo(), cargarPuntuaciones()])
})
</script>

<!-- ── Componentes inline ──────────────────────────────────────────────────── -->
<script>
import { defineComponent, h } from 'vue'
import AvatarPilotoCmp from '@/components/media/AvatarPiloto.vue'

const SlotEquipoCard = defineComponent({
  props: { titulo: String, subtitulo: String, max: Number },
  setup(props, { slots }) {
    return () => h('div', { class: 'card space-y-2' }, [
      h('p', { class: 'text-zinc-400 text-xs font-semibold uppercase tracking-wider' }, [
        props.titulo,
        h('span', { class: 'text-zinc-700 font-normal normal-case ml-1' }, `· ${props.subtitulo}`)
      ]),
      slots.default?.()
    ])
  }
})

const FilaPiloto = defineComponent({
  props: { piloto: Object },
  emits: ['vender'],
  setup(props, { emit }) {
    return () => h('div', { class: 'flex items-center gap-2 p-2 rounded-lg bg-zinc-800/50' }, [
      h(AvatarPilotoCmp, { piloto: props.piloto, size: 'sm' }),
      h('div', { class: 'flex-1 min-w-0' }, [
        h('p', { class: 'text-white text-sm font-medium truncate' },
          `${props.piloto.nombre} ${props.piloto.apellido}`),
        h('p', { class: 'text-zinc-500 text-xs' },
          `${props.piloto.escuderia?.nombre || '—'} · ${(props.piloto.precio / 1_000_000).toFixed(1)}M`)
      ]),
      h('button', {
        onClick: () => emit('vender'),
        class: 'text-zinc-600 hover:text-red-400 text-xs px-1 transition-colors'
      }, '✕')
    ])
  }
})

const SlotVacio = defineComponent({
  setup() {
    return () => h('p', {
      class: 'text-zinc-700 text-sm text-center py-1.5 border border-dashed border-zinc-800 rounded-lg'
    }, 'Slot vacío — compra en el mercado')
  }
})

export default {
  name: 'LeagueDetailView',
  components: { SlotEquipoCard, FilaPiloto, SlotVacio }
}
</script>
