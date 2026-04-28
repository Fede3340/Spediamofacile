import { proxyToBackend } from '../../utils/backendProxy'

export default defineEventHandler((event) => proxyToBackend(event, '/auth'))
