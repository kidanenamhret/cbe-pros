'use server';
import { getSession } from '@/lib/session';
import { query } from '@/lib/db';

export async function getUserProfile() {
  const session = await getSession();
  if (!session) return null;

  try {
    const users: any = await query(
      'SELECT id, username, fullname, email, phone, role, created_at FROM users WHERE id = ?',
      [session.user.id]
    );
    return users[0] || null;
  } catch {
    return session.user;
  }
}
