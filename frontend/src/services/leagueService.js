import api from './api'

export const ligaService = {
  getLigas:        ()       => api.get('/ligas'),
  getLiga:         (id)     => api.get(`/ligas/${id}`),
  crearLiga:       (datos)  => api.post('/ligas', datos),
  unirseALiga:     (codigo) => api.post('/ligas/unirse', { codigo }),
  eliminarLiga:    (id)     => api.delete(`/ligas/${id}`),
  getMercado:      (id)     => api.get(`/ligas/${id}/mercado`),
  getPuntuaciones: (id)     => api.get(`/ligas/${id}/puntuaciones`),
}

export const equipoService = {
  getEquipo:          (ligaId)            => api.get(`/ligas/${ligaId}/equipo`),
  getEquipoDeUsuario: (ligaId, usuarioId) => api.get(`/ligas/${ligaId}/equipo/usuario/${usuarioId}`),

  // Pilotos
  comprarPiloto: (ligaId, pilotoId) => api.post(`/ligas/${ligaId}/equipo/pilotos`, { piloto_id: pilotoId }),
  venderPiloto:  (ligaId, pilotoId) => api.delete(`/ligas/${ligaId}/equipo/pilotos/${pilotoId}`),

  // Coche
  comprarCoche: (ligaId, cocheId) => api.post(`/ligas/${ligaId}/equipo/coche`, { coche_id: cocheId }),
  venderCoche:  (ligaId, cocheId) => api.delete(`/ligas/${ligaId}/equipo/coche/${cocheId}`),

  // Escudería
  comprarEscuderia: (ligaId, escuderiaId) => api.post(`/ligas/${ligaId}/equipo/escuderia`, { escuderia_id: escuderiaId }),
  venderEscuderia:  (ligaId, escuderiaId) => api.delete(`/ligas/${ligaId}/equipo/escuderia/${escuderiaId}`),

  // Robar (transferencias entre equipos de la misma liga)
  robarPiloto:    (ligaId, pilotoId)    => api.post(`/ligas/${ligaId}/equipo/robar/pilotos`,   { piloto_id: pilotoId }),
  robarEscuderia: (ligaId, escuderiaId) => api.post(`/ligas/${ligaId}/equipo/robar/escuderia`, { escuderia_id: escuderiaId }),
  robarCoche:     (ligaId, cocheId)     => api.post(`/ligas/${ligaId}/equipo/robar/coche`,     { coche_id: cocheId }),
}
