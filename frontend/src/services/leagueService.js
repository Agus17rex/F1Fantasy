import api from './api'

export const ligaService = {
  getLigas:         ()     => api.get('/ligas'),
  getLiga:          (id)   => api.get(`/ligas/${id}`),
  crearLiga:        (data) => api.post('/ligas', data),
  unirseALiga:      (code) => api.post('/ligas/unirse', { code }),
  eliminarLiga:     (id)   => api.delete(`/ligas/${id}`),
  getMercado:       (id)   => api.get(`/ligas/${id}/mercado`),
  getPuntuaciones:  (id)   => api.get(`/ligas/${id}/puntuaciones`),
}

export const equipoService = {
  getEquipo:          (ligaId)         => api.get(`/ligas/${ligaId}/equipo`),
  getEquipoDeUsuario: (ligaId, userId) => api.get(`/ligas/${ligaId}/equipo/usuario/${userId}`),

  // Pilotos
  comprarPiloto: (ligaId, pilotoId) => api.post(`/ligas/${ligaId}/equipo/pilotos`, { driver_id: pilotoId }),
  venderPiloto:  (ligaId, pilotoId) => api.delete(`/ligas/${ligaId}/equipo/pilotos/${pilotoId}`),

  // Director
  comprarDirector: (ligaId, directorId) => api.post(`/ligas/${ligaId}/equipo/director`, { director_id: directorId }),
  venderDirector:  (ligaId, directorId) => api.delete(`/ligas/${ligaId}/equipo/director/${directorId}`),

  // Escudería
  comprarEscuderia: (ligaId, escuderiaId) => api.post(`/ligas/${ligaId}/equipo/escuderia`, { constructor_id: escuderiaId }),
  venderEscuderia:  (ligaId, escuderiaId) => api.delete(`/ligas/${ligaId}/equipo/escuderia/${escuderiaId}`),

  // Robar (steal from another team in the same league)
  robarPiloto:    (ligaId, pilotoId)    => api.post(`/ligas/${ligaId}/equipo/robar/pilotos`,   { piloto_id: pilotoId }),
  robarEscuderia: (ligaId, escuderiaId) => api.post(`/ligas/${ligaId}/equipo/robar/escuderia`, { escuderia_id: escuderiaId }),
  robarDirector:  (ligaId, directorId)  => api.post(`/ligas/${ligaId}/equipo/robar/director`,  { director_id: directorId }),
}
