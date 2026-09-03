'use client';

import { useCallback, useEffect, useState } from 'react';
import { api, getAuthToken, setAuthToken } from '@/lib/api';
import type { User } from '@/lib/types';

interface AuthState {
  user: User | null;
  loading: boolean;
}

export function useAuth() {
  const [state, setState] = useState<AuthState>({ user: null, loading: true });

  useEffect(() => {
    const token = getAuthToken();
    if (!token) {
      setState({ user: null, loading: false });
      return;
    }

    api
      .get<{ user: User }>('/api/auth/me')
      .then((res) => setState({ user: res.user, loading: false }))
      .catch(() => {
        setAuthToken(null);
        setState({ user: null, loading: false });
      });
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const res = await api.post<{ token: string; user: User }>('/api/auth/login', {
      email,
      password,
    });
    setAuthToken(res.token);
    setState({ user: res.user, loading: false });
  }, []);

  const logout = useCallback(async () => {
    await api.post('/api/auth/logout').catch(() => {});
    setAuthToken(null);
    setState({ user: null, loading: false });
  }, []);

  return { ...state, login, logout };
}
