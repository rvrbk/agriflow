import axios from 'axios';
import { queueRequestFromConfig } from '../services/offlineQueue';

let authRedirectInProgress = false;

axios.defaults.baseURL = window.location.origin;
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

axios.interceptors.response.use(
	(response) => response,
	(error) => {
		const method = String(error?.config?.method ?? '').toLowerCase();
		const canQueue = ['post', 'put', 'patch', 'delete'].includes(method);
		const noServerResponse = !error?.response;

		if (canQueue && noServerResponse) {
			const queued = queueRequestFromConfig(error.config);

			if (queued) {
				return Promise.resolve({
					data: {
						queued: true,
						offline: true,
						queueId: queued.id,
					},
					status: 202,
					statusText: 'Accepted (Queued Offline)',
					headers: {},
					config: error.config,
					request: error.request,
				});
			}
		}

		const status = error?.response?.status;

		if (status === 401 || status === 419) {
			const pathname = window.location.pathname;
			const isGuestRoute = pathname === '/login' || pathname === '/set-password' || pathname === '/forgot-password';
			const requestUrl = String(error?.config?.url ?? '');
			const isCurrentUserProbe = requestUrl.includes('/api/user');

			// Let the router/auth store handle the bootstrap user probe to avoid hard-refresh loops.
			if (!isGuestRoute && !isCurrentUserProbe && !authRedirectInProgress) {
				authRedirectInProgress = true;
				const redirect = encodeURIComponent(window.location.pathname + window.location.search);
				window.location.replace(`/login?redirect=${redirect}`);
			}
		}

		return Promise.reject(error);
	},
);

if (window.__AGRIFLOW_E2E__ === true) {
	window.__AGRIFLOW_HTTP__ = axios;
}

export default axios;
