// Centralized API client. All frontend requests go through here so the
// base URL and auth token live in one place.

const API_URL =
  process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

let token: string | null = null;

export function setAuthToken(value: string | null): void {
  token = value;
  if (typeof window !== 'undefined') {
    if (value) {
      localStorage.setItem('devhost_token', value);
    } else {
      localStorage.removeItem('devhost_token');
    }
  }
}

export function getAuthToken(): string | null {
  if (token) return token;
  if (typeof window !== 'undefined') {
    token = localStorage.getItem('devhost_token');
  }
  return token;
}

export function logout(): void {
  setAuthToken(null);
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

async function request<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(options.headers as Record<string, string>),
  };

  const auth = getAuthToken();
  if (auth) {
    headers['Authorization'] = `Bearer ${auth}`;
  }

  const res = await fetch(`${API_URL}${path}`, {
    ...options,
    headers,
  });

  if (res.status === 204) {
    return undefined as T;
  }

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    const message = data?.message ?? `Request failed (${res.status})`;
    throw { message, errors: data?.errors } as ApiError;
  }

  return data as T;
}

export const api = {
  get: <T>(path: string) => request<T>(path),
  post: <T>(path: string, body?: unknown) =>
    request<T>(path, { method: 'POST', body: body ? JSON.stringify(body) : undefined }),
  patch: <T>(path: string, body?: unknown) =>
    request<T>(path, { method: 'PATCH', body: body ? JSON.stringify(body) : undefined }),
  del: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
};
