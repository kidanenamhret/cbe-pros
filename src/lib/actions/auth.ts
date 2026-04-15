'use server';

import { query } from '@/lib/db';
import bcrypt from 'bcryptjs';
import { login, logout, encrypt, decrypt } from '@/lib/session';
import { redirect } from 'next/navigation';
import nodemailer from 'nodemailer';

// Email transporter — reads from .env.local
// Uses Gmail SMTP by default. Works in production too (Vercel env vars).
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'smtp.ethereal.email',
  port: parseInt(process.env.SMTP_PORT || '587'),
  secure: false, // TLS
  auth: {
    user: process.env.SMTP_USER || 'yvonne.connelly97@ethereal.email',
    pass: process.env.SMTP_PASS || 'P9F84pBwzH3aW1XWwX',
  },
});

export async function authenticate(formData: FormData) {
  
  const email = formData.get('email') as string;
  const password = formData.get('password') as string;

  if (!email || !password) {
    return { error: 'Please enter both email and password.' };
  }

  try {
    const users: any = await query('SELECT * FROM users WHERE email = ? LIMIT 1', [email]);
    
    if (users.length === 0) {
      return { error: 'Invalid credentials.' };
    }

    const user = users[0];
    const isPasswordValid = await bcrypt.compare(password, user.password_hash);

    if (!isPasswordValid) {
      // Logic for failed attempts could go here (updating login_attempts table)
      return { error: 'Invalid credentials.' };
    }

    // Check if locked
    if (user.locked_until && new Date(user.locked_until) > new Date()) {
      return { error: 'Account is temporarily locked.' };
    }

    // Prepare session data (don't include sensitive info)
    const sessionUser = {
      id: user.id,
      username: user.username,
      fullname: user.fullname,
      email: user.email,
      role: user.role
    };

    await login(sessionUser);
    
    // Update last login
    await query('UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?', [user.id]);
    
  } catch (err: any) {
    console.error('Auth Error:', err);
    return { error: 'An unexpected system error occurred.' };
  }

  // Route based on role
  const sessionCheck = await (await import('@/lib/session')).getSession();
  if (sessionCheck?.user?.role === 'admin') {
    redirect('/admin');
  }
  redirect('/dashboard');
}

export async function register(formData: FormData) {
  const fullname = formData.get('fullname') as string;
  const email = formData.get('email') as string;
  const phone = formData.get('phone') as string;
  const password = formData.get('password') as string;

  if (!fullname || !email || !password) {
    return { error: 'Please enter all required fields.' };
  }

  try {
    // Check if user already exists
    const existingUsers: any = await query('SELECT id FROM users WHERE email = ? LIMIT 1', [email]);
    if (existingUsers.length > 0) {
      return { error: 'A vault is already registered to this email.' };
    }

    // Generate username
    const username = email.split('@')[0] + Math.floor(Math.random() * 1000);
    const passwordHash = await bcrypt.hash(password, 10);
    const accountNumber = '1000' + Math.floor(Math.random() * 100000000).toString().padStart(8, '0');

    // Create User, Profile, and Default Account in a transaction
    const pool = (await import('@/lib/db')).default;
    const connection = await pool.getConnection();

    try {
      await connection.beginTransaction();

      const [userResult]: any = await connection.execute(
        'INSERT INTO users (username, fullname, email, password_hash, phone) VALUES (?, ?, ?, ?, ?)',
        [username, fullname, email, passwordHash, phone || null]
      );
      
      const newUserId = userResult.insertId;

      await connection.execute(
        'INSERT INTO accounts (user_id, account_number, balance, account_type, status) VALUES (?, ?, ?, ?, ?)',
        [newUserId, accountNumber, 500.00, 'checking', 'active'] // Give $500 starting bonus for demo
      );

      await connection.commit();
      
      // Auto-login new user
      const sessionUser = {
        id: newUserId,
        username,
        fullname,
        email,
        role: 'user'
      };
      await login(sessionUser);

    } catch (err) {
      await connection.rollback();
      throw err;
    } finally {
      connection.release();
    }
  } catch (err) {
    console.error('Registration Error:', err);
    return { error: 'System error during registration.' };
  }

  redirect('/dashboard');
}

export async function signOut() {
  await logout();
  redirect('/');
}

export async function requestPasswordReset(formData: FormData) {
  const email = formData.get('email') as string;
  if (!email) return { error: 'Email is required.' };

  try {
    const users: any = await query('SELECT id, fullname FROM users WHERE email = ? LIMIT 1', [email]);
    if (users.length === 0) {
      // Fake success for security
      return { success: 'If an account exists, a reset link was sent.' };
    }

    const user = users[0];
    
    // Generate a reset token valid for 30 minutes
    const token = await encrypt({ id: user.id, purpose: 'reset_password', exp: Math.floor(Date.now() / 1000) + (30 * 60) });
    const baseUrl = process.env.APP_URL || 'http://localhost:3000';
    const resetUrl = `${baseUrl}/reset-password?token=${token}`;

    const mailOptions = {
      from: `"Mesfin Bank Security" <${process.env.SMTP_FROM || process.env.SMTP_USER || 'security@mesfinbank.com'}>`,
      to: email,
      subject: 'Password Reset Request',
      text: `Hello ${user.fullname},\n\nPlease click the link below to reset your password:\n${resetUrl}\n\nThis link is valid for 30 minutes. If you did not request this, please ignore this email.`,
      html: `
        <div style="font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #1e1b4b; border-radius: 10px; background: #020617; color: white;">
          <h2 style="color: #8b5cf6;">Password Reset Request</h2>
          <p>Hello <b>${user.fullname}</b>,</p>
          <p>Please click the button below to reset your password:</p>
          <a href="${resetUrl}" style="display: inline-block; padding: 10px 20px; background: #8b5cf6; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Reset Password</a>
          <p style="font-size: 12px; color: #64748b; margin-top: 20px;">This link is valid for 30 minutes. If you did not request this, please ignore this email.</p>
        </div>
      `
    };

    const info = await transporter.sendMail(mailOptions);
    console.log('Preview URL: %s', nodemailer.getTestMessageUrl(info));

    return { 
      success: 'If an account exists, a reset link was sent.',
      previewUrl: nodemailer.getTestMessageUrl(info) // For demo purposes only
    };
  } catch (err) {
    console.error('Password reset request error:', err);
    return { error: 'System error during password reset request.' };
  }
}

export async function confirmPasswordReset(formData: FormData, token: string) {
  const newPassword = formData.get('password') as string;
  if (!newPassword || newPassword.length < 8) return { error: 'Password must be at least 8 characters.' };

  try {
    const payload = await decrypt(token);
    if (!payload?.id || payload.purpose !== 'reset_password') {
      return { error: 'Invalid or expired reset token.' };
    }

    const passwordHash = await bcrypt.hash(newPassword, 10);
    await query('UPDATE users SET password_hash = ? WHERE id = ?', [passwordHash, payload.id]);
    
    return { success: 'Password has been successfully updated.' };
  } catch (err) {
    console.error('Password reset error:', err);
    return { error: 'Invalid or expired reset token.' };
  }
}
