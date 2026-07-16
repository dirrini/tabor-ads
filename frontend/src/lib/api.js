const API_URL = import.meta.env.VITE_API_URL || ''
let csrfReady = false

function currentLocale() {
  return localStorage.getItem('impressiontrack.locale') || 'pt-BR'
}

function genericErrorMessage() {
  return currentLocale() === 'en'
    ? 'Could not complete the operation.'
    : 'Não foi possível concluir a operação.'
}

function cookie(name) {
  const value = document.cookie.split('; ').find((row) => row.startsWith(`${name}=`))?.split('=')[1]
  return value ? decodeURIComponent(value) : ''
}

export async function api(path, options = {}) {
  const method = (options.method || 'GET').toUpperCase()

  if (!csrfReady && !['GET', 'HEAD'].includes(method)) {
    const csrfResponse = await fetch(`${API_URL}/api/csrf-cookie`, {
      credentials: 'include',
      headers: { 'Accept-Language': currentLocale() },
    })

    if (!csrfResponse.ok) {
      const error = new Error(genericErrorMessage())
      error.status = csrfResponse.status
      throw error
    }

    csrfReady = true
  }

  const response = await fetch(`${API_URL}${path}`, {
    credentials: 'include',
    ...options,
    headers: {
      Accept: 'application/json',
      'Accept-Language': currentLocale(),
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(!['GET', 'HEAD'].includes(method) ? { 'X-XSRF-TOKEN': cookie('XSRF-TOKEN') } : {}),
      ...options.headers,
    },
  })

  const data = response.status === 204 ? null : await response.json().catch(() => ({}))

  if (!response.ok) {
    const message = data?.message || Object.values(data?.errors || {})?.flat()?.[0] || genericErrorMessage()
    const error = new Error(message)
    error.status = response.status
    error.data = data
    throw error
  }

  return data
}

export { API_URL }
