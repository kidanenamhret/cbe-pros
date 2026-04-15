'use server';

import { query } from '@/lib/db';
import pool from '@/lib/db';
import bcrypt from 'bcryptjs';
import { getSession } from '@/lib/session';
import { revalidatePath } from 'next/cache';
import { redirect } from 'next/navigation';

async function assertAdmin() {
  const session = await getSession();
  if (!session || session.user.role !== 'admin') {
    redirect('/dashboard');
  }
  return session;
}

export async function getAdminStats() {
  await assertAdmin();
  try {
    const totalUsers: any = await query('SELECT COUNT(*) as count FROM users');
    const totalAccounts: any = await query('SELECT COUNT(*) as count FROM accounts');
    const totalTransactions: any = await query('SELECT COUNT(*) as count FROM transactions');
    const totalBalance: any = await query('SELECT SUM(balance) as total FROM accounts');
    const allUsers: any = await query(`
      SELECT u.id, u.username, u.fullname, u.email, u.role, u.created_at,
             COUNT(a.id) as account_count,
             COALESCE(SUM(a.balance), 0) as total_balance
      FROM users u
      LEFT JOIN accounts a ON u.id = a.user_id
      GROUP BY u.id
      ORDER BY u.created_at DESC
    `);

    return {
      totalUsers: totalUsers[0].count,
      totalAccounts: totalAccounts[0].count,
      totalTransactions: totalTransactions[0].count,
      totalBalance: parseFloat(totalBalance[0].total || '0'),
      users: allUsers
    };
  } catch (err) {
    console.error('Admin stats error:', err);
    return null;
  }
}

export async function adminCreateUser(formData: FormData) {
  await assertAdmin();
  const fullname = formData.get('fullname') as string;
  const email = formData.get('email') as string;
  const phone = formData.get('phone') as string;
  const password = formData.get('password') as string;
  const role = formData.get('role') as string || 'user';
  const initialBalance = parseFloat(formData.get('initial_balance') as string || '0');

  if (!fullname || !email || !password) return { error: 'Missing required fields.' };

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    const existing: any = await connection.execute('SELECT id FROM users WHERE email = ?', [email]);
    if ((existing[0] as any[]).length > 0) {
      await connection.rollback();
      return { error: 'Email already registered.' };
    }

    const username = email.split('@')[0] + Math.floor(Math.random() * 1000);
    const hash = await bcrypt.hash(password, 10);
    const accountNumber = '1000' + Math.floor(Math.random() * 100000000).toString().padStart(8, '0');

    const [result]: any = await connection.execute(
      'INSERT INTO users (username, fullname, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?, ?)',
      [username, fullname, email, hash, role, phone || null]
    );

    await connection.execute(
      'INSERT INTO accounts (user_id, account_number, balance, account_type, status) VALUES (?, ?, ?, ?, ?)',
      [result.insertId, accountNumber, initialBalance, 'checking', 'active']
    );

    await connection.commit();
    revalidatePath('/admin');
    return { success: `User ${fullname} created. Account: ${accountNumber}` };
  } catch (err: any) {
    await connection.rollback();
    return { error: err.message };
  } finally {
    connection.release();
  }
}

export async function adminToggleUserStatus(userId: number, action: 'freeze' | 'unfreeze' | 'delete') {
  await assertAdmin();
  try {
    if (action === 'delete') {
      await query('DELETE FROM users WHERE id = ?', [userId]);
    } else {
      const status = action === 'freeze' ? 'frozen' : 'active';
      await query('UPDATE accounts SET status = ? WHERE user_id = ?', [status, userId]);
    }
    revalidatePath('/admin');
    return { success: true };
  } catch (err) {
    return { error: 'Operation failed.' };
  }
}
