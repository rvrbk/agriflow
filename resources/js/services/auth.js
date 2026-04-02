import http from '../lib/http';

export async function fetchCsrfCookie() {
    await http.get('/sanctum/csrf-cookie');
}

export async function login(credentials) {
    await fetchCsrfCookie();
    await http.post('/login', credentials);
}

export async function logout() {
    await http.post('/logout');
}

export async function getCurrentUser() {
    const response = await http.get('/api/user');

    return response.data;
}
