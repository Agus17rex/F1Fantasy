import api from './api'

export const adminService = {
  getPanelControl:         ()              => api.get('/admin/panel'),
  getCarreras:             ()              => api.get('/admin/carreras'),
  sincronizarDatosF1:      (season)        => api.post('/admin/sincronizar', { season }),
  sincronizarResultados:   (id)            => api.post(`/admin/carreras/${id}/sincronizar`),
  puntuarCarrera:          (id)            => api.post(`/admin/carreras/${id}/puntuar`),
  actualizarPrecios:       (season)        => api.post('/admin/precios', { season }),
  getPuntuacionCarrera:    (id)            => api.get(`/admin/carreras/${id}/puntuacion`),
  actualizarPenalizaciones:(resultadoId, data) => api.patch(`/admin/resultados/${resultadoId}/penalizaciones`, data),
  recalcularTodo:          ()              => api.post('/admin/recalcular'),
}
