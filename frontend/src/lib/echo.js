import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { api } from './api'

window.Pusher = Pusher

export function createEcho() {
  return new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'local-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || window.location.port || 80),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || window.location.port || 443),
    forceTLS: window.location.protocol === 'https:',
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel) => ({
      authorize: async (socketId, callback) => {
        try {
          const response = await api('/broadcasting/auth', {
            method: 'POST',
            body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
          })
          callback(null, response)
        } catch (error) { callback(error) }
      },
    }),
  })
}
