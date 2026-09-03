'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

export default function RootPage() {
  const router = useRouter();

  useEffect(() => {
    const token =
      typeof window !== 'undefined'
        ? localStorage.getItem('devhost_token')
        : null;
    router.push(token ? '/dashboard' : '/login');
  }, [router]);

  return null;
}
