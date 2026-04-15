'use server';

import { query } from '@/lib/db';
import pool from '@/lib/db';
import { getSession } from '@/lib/session';
import { revalidatePath } from 'next/cache';

export async function getTransactionHistory(limit = 50) {
  const session = await getSession();
  if (!session) return null;

  const transactions: any = await query(
    `SELECT t.*, 
      sa.account_number as sender_acc_number, 
      ra.account_number as receiver_acc_number,
      CASE WHEN t.sender_account_id IN (SELECT id FROM accounts WHERE user_id = ?) THEN 'debit' ELSE 'credit' END as direction
    FROM transactions t
    LEFT JOIN accounts sa ON t.sender_account_id = sa.id
    LEFT JOIN accounts ra ON t.receiver_account_id = ra.id
    WHERE t.sender_account_id IN (SELECT id FROM accounts WHERE user_id = ?)
       OR t.receiver_account_id IN (SELECT id FROM accounts WHERE user_id = ?)
    ORDER BY t.created_at DESC LIMIT ?`,
    [session.user.id, session.user.id, session.user.id, limit]
  );
  return transactions;
}

export async function getGoals() {
  const session = await getSession();
  if (!session) return [];

  try {
    const goals: any = await query(
      'SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC',
      [session.user.id]
    );
    return goals;
  } catch {
    return [];
  }
}

export async function createGoal(formData: FormData) {
  const session = await getSession();
  if (!session) return { error: 'Unauthorized' };

  const title = formData.get('title') as string;
  const targetAmount = parseFloat(formData.get('target') as string);

  if (!title || isNaN(targetAmount) || targetAmount < 100) {
    return { error: 'Title and target amount (min 100 ETB) required.' };
  }

  try {
    await query(
      'INSERT INTO savings_goals (user_id, title, target_amount, current_amount, status) VALUES (?, ?, ?, 0, "active")',
      [session.user.id, title, targetAmount]
    );
    revalidatePath('/dashboard/goals');
    return { success: true };
  } catch (err: any) {
    // If table doesn't exist, create it first
    if (err.code === 'ER_NO_SUCH_TABLE') {
      await query(`CREATE TABLE IF NOT EXISTS savings_goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        target_amount DECIMAL(15,2) NOT NULL,
        current_amount DECIMAL(15,2) DEFAULT 0,
        status ENUM('active','completed','cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      )`);
      await query(
        'INSERT INTO savings_goals (user_id, title, target_amount) VALUES (?, ?, ?)',
        [session.user.id, title, targetAmount]
      );
      revalidatePath('/dashboard/goals');
      return { success: true };
    }
    return { error: err.message };
  }
}

export async function contributeToGoal(formData: FormData) {
  const session = await getSession();
  if (!session) return { error: 'Unauthorized' };

  const goalId = formData.get('goal_id');
  const amount = parseFloat(formData.get('amount') as string);
  const sourceAccount = formData.get('source_account') as string;

  if (!goalId || isNaN(amount) || amount <= 0) return { error: 'Invalid contribution.' };

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    const [accounts]: any = await connection.execute(
      'SELECT id, balance FROM accounts WHERE account_number = ? AND user_id = ? FOR UPDATE',
      [sourceAccount, session.user.id]
    );
    if (!accounts.length || accounts[0].balance < amount) {
      throw new Error('Insufficient funds.');
    }

    await connection.execute('UPDATE accounts SET balance = balance - ? WHERE id = ?', [amount, accounts[0].id]);
    await connection.execute('UPDATE savings_goals SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?', [amount, goalId, session.user.id]);
    await connection.execute(
      'INSERT INTO transactions (sender_account_id, amount, description, type, status) VALUES (?, ?, ?, "withdrawal", "completed")',
      [accounts[0].id, amount, `Savings Goal Contribution`]
    );

    await connection.commit();
    revalidatePath('/dashboard/goals');
    return { success: true };
  } catch (err: any) {
    await connection.rollback();
    return { error: err.message };
  } finally {
    connection.release();
  }
}

export async function getBeneficiaries() {
  const session = await getSession();
  if (!session) return [];
  try {
    const rows: any = await query(
      `SELECT b.*, u.fullname as beneficiary_fullname FROM beneficiaries b
       LEFT JOIN users u ON b.beneficiary_user_id = u.id
       WHERE b.user_id = ? ORDER BY b.created_at DESC`,
      [session.user.id]
    );
    return rows;
  } catch { return []; }
}

export async function addBeneficiary(formData: FormData) {
  const session = await getSession();
  if (!session) return { error: 'Unauthorized' };

  const accountNumber = formData.get('account_number') as string;
  const nickname = formData.get('nickname') as string;

  if (!accountNumber) return { error: 'Account number required.' };

  try {
    const accounts: any = await query(
      'SELECT a.id, a.user_id, u.fullname FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.account_number = ?',
      [accountNumber]
    );
    if (!accounts.length) return { error: 'Account not found in the system.' };

    const target = accounts[0];
    await query(
      'INSERT INTO beneficiaries (user_id, beneficiary_user_id, beneficiary_account, beneficiary_name, nickname) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nickname = VALUES(nickname)',
      [session.user.id, target.user_id, accountNumber, target.fullname, nickname || target.fullname]
    );
    revalidatePath('/dashboard/beneficiaries');
    return { success: true, name: target.fullname };
  } catch (err: any) {
    return { error: err.message };
  }
}

export async function getNotifications() {
  const session = await getSession();
  if (!session) return [];
  try {
    const rows: any = await query(
      'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30',
      [session.user.id]
    );
    return rows;
  } catch { return []; }
}

export async function markNotificationsRead() {
  const session = await getSession();
  if (!session) return;
  await query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [session.user.id]);
  revalidatePath('/dashboard/notifications');
}

export async function telebirrService(formData: FormData) {
  const session = await getSession();
  if (!session) return { error: 'Unauthorized' };

  const type = formData.get('type') as string;
  const accountId = formData.get('account_id');
  const phone = formData.get('phone') as string;
  const amount = parseFloat(formData.get('amount') as string);

  if (!accountId || !phone || isNaN(amount) || amount <= 0) {
    return { error: 'All fields are required.' };
  }

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    const [accounts]: any = await connection.execute(
      'SELECT id, balance FROM accounts WHERE id = ? AND user_id = ? FOR UPDATE',
      [accountId, session.user.id]
    );
    if (!accounts.length) throw new Error('Account not found.');
    if (accounts[0].balance < amount) throw new Error('Insufficient funds.');

    await connection.execute('UPDATE accounts SET balance = balance - ? WHERE id = ?', [amount, accountId]);
    const desc = type === 'airtime' ? `Airtime for ${phone}` : `Telebirr Wallet Transfer to ${phone}`;
    await connection.execute(
      'INSERT INTO transactions (sender_account_id, amount, description, type, status) VALUES (?, ?, ?, "withdrawal", "completed")',
      [accountId, amount, desc]
    );

    await connection.commit();
    revalidatePath('/dashboard/telebirr');
    return { success: true, message: type === 'airtime' ? `Airtime recharged to ${phone}` : `Transferred to ${phone} wallet` };
  } catch (err: any) {
    await connection.rollback();
    return { error: err.message };
  } finally {
    connection.release();
  }
}

export async function updateProfile(formData: FormData) {
  const session = await getSession();
  if (!session) return { error: 'Unauthorized' };

  const phone = formData.get('phone') as string;
  const address = formData.get('address') as string;

  try {
    await query('UPDATE users SET phone = ? WHERE id = ?', [phone, session.user.id]);
    return { success: true };
  } catch (err: any) {
    return { error: err.message };
  }
}

export async function changePassword(formData: FormData) {
  const session = await getSession();
  if (!session) return { error: 'Unauthorized' };

  const currentPassword = formData.get('current_password') as string;
  const newPassword = formData.get('new_password') as string;
  const confirmPassword = formData.get('confirm_password') as string;

  if (newPassword !== confirmPassword) return { error: 'New passwords do not match.' };
  if (newPassword.length < 8) return { error: 'Password must be at least 8 characters.' };

  try {
    const bcrypt = (await import('bcryptjs')).default;
    const users: any = await query('SELECT password_hash FROM users WHERE id = ?', [session.user.id]);
    const valid = await bcrypt.compare(currentPassword, users[0].password_hash);
    if (!valid) return { error: 'Current password is incorrect.' };

    const hash = await bcrypt.hash(newPassword, 10);
    await query('UPDATE users SET password_hash = ? WHERE id = ?', [hash, session.user.id]);
    return { success: true };
  } catch (err: any) {
    return { error: err.message };
  }
}
