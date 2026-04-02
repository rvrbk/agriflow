import axios from 'axios';

axios.defaults.baseURL = window.location.origin;
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

axios.interceptors.response.use(
	(response) => response,
	(error) => {
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
