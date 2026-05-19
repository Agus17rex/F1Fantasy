import api from './api'

export const f1Service = {
  // Pilotos
  getPilotos:   ()   => api.get('/f1/pilotos'),
  getPiloto:    (id) => api.get(`/f1/pilotos/${id}`),

  // Escuderías
  getEscuderias:  ()   => api.get('/f1/escuderias'),
  getEscuderia:   (id) => api.get(`/f1/escuderias/${id}`),

  // Carreras
  getCarreras:      ()   => api.get('/f1/carreras'),
  getCarrera:       (id) => api.get(`/f1/carreras/${id}`),
  getProximaCarrera:()   => api.get('/f1/carreras/proxima'),

  // Clasificaciones
  getClasificacionPilotos:    () => api.get('/f1/clasificacion/pilotos'),
  getClasificacionEscuderias: () => api.get('/f1/clasificacion/escuderias'),

  // Coches
  getCoches: () => api.get('/f1/coches'),

  // Reglas de puntuación
  getReglas: () => api.get('/f1/reglas'),

  // Ranking fantasy global
  getFantasyRanking: () => api.get('/f1/fantasy-ranking'),
}
