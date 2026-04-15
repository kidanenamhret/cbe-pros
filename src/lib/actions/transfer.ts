'use server';

import { query } from '@/lib/db';
import pool from '@/lib/db';
import { getSession } from '@/lib/session';
import { revalidatePath } from 'next/cache';

export async function getDashboardData() {
  const session = await getSession();
  if (!session) return null;

  try {
    const accounts: any = await query(
      'SELECT * FROM accounts WHERE user_id = ? AND status = "active"',
      [session.user.id]
    );

    const transactions: any = await query(
      `SELECT t.*, 
        CASE 
          WHEN t.sender_account_id IN (SELECT id FROM accounts WHERE user_id = ?) THEN 'debit'
          ELSE 'credit'
        END as direction
      FROM transactions t 
      WHERE t.sender_account_id IN (SELECT id FROM accounts WHERE user_id = ?) 
         OR t.receiver_account_id IN (SELECT id FROM accounts WHERE user_id = ?) 
      ORDER BY t.created_at DESC LIMIT 5`,
      [session.user.id, session.user.id, session.user.id]
    );

    return { accounts, transactions };
  } catch (err) {
    console.error('Data Fetch Error:', err);
    return null;
  }
}

export async function executeTransfer(formData: FormData) {
  const session = await getSession();
  if (!session) return { error: 'Unauthorized' };

  const senderAccountId = formData.get('sender_account_id');
  const receiverAccountNumber = formData.get('receiver_account');
  const amount = parseFloat(formData.get('amount') as string);
  const description = formData.get('description') as string;

  if (!senderAccountId || !receiverAccountNumber || isNaN(amount) || amount <= 0) {
    return { error: 'Invalid transfer details.' };
  }

  const connection = await pool.getConnection();

  try {
    await connection.beginTransaction();

    // 1. Verify Sender and Balance
    const [senders]: any = await connection.execute(
      'SELECT id, balance FROM accounts WHERE id = ? AND user_id = ? FOR UPDATE',
      [senderAccountId, session.user.id]
    );

    if (senders.length === 0) throw new Error('Source account not found.');
    if (senders[0].balance < amount) throw new Error('Insufficient funds.');

    // 2. Locate Receiver
    const [receivers]: any = await connection.execute(
      'SELECT id FROM accounts WHERE account_number = ? FOR UPDATE',
      [receiverAccountNumber]
    );

    if (receivers.length === 0) throw new Error('Recipient account not found.');
    const receiverId = receivers[0].id;

    // 3. Subtract from Sender
    await connection.execute(
      'UPDATE accounts SET balance = balance - ? WHERE id = ?',
      [amount, senderAccountId]
    );

    // 4. Add to Receiver
    await connection.execute(
      'UPDATE accounts SET balance = balance + ? WHERE id = ?',
      [amount, receiverId]
    );

    // 5. Log Transaction (Ref handled by DB trigger)
    await connection.execute(
      'INSERT INTO transactions (sender_account_id, receiver_account_id, amount, description, type, status) VALUES (?, ?, ?, ?, "transfer", "completed")',
      [senderAccountId, receiverId, amount, description]
    );

    await connection.commit();
    revalidatePath('/dashboard');
    return { success: true };
    
  } catch (err: any) {
    await connection.rollback();
    console.error('Transfer Error:', err);
    return { error: err.message || 'Transaction failed.' };
  } finally {
    connection.release();
  }
}
