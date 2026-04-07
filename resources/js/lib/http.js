import axios from 'axios';
import { queueRequestFromConfig } from '../services/offlineQueue';

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
			const isLoginRoute = window.location.pathname === '/login';

			if (!isLoginRoute) {
				const redirect = encodeURIComponent(window.location.pathname + window.location.search);
				window.location.assign(`/login?redirect=${redirect}`);
			}
		}

		return Promise.reject(error);
	},
);

export default axios;
