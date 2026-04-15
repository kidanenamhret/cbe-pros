# 🏦 Mesfin Digital Bank

A modern, full-stack digital banking platform built with **Next.js 16**, **TypeScript**, **Tailwind CSS**, and **MySQL**.

---

## ✨ Features

| Feature | Status |
|---|---|
| User Registration & Login | ✅ |
| JWT Session Authentication | ✅ |
| Email Password Reset (Gmail) | ✅ |
| Real-Time Dashboard | ✅ |
| Fund Transfers (Atomic DB) | ✅ |
| Telebirr Services | ✅ |
| Savings Goals | ✅ |
| Beneficiaries Manager | ✅ |
| Transaction Ledger | ✅ |
| Notifications Inbox | ✅ |
| Account Settings | ✅ |
| Admin Command Center | ✅ |
| Mobile Responsive (PWA-ready) | ✅ |
| Real-Time Charting | ✅ |

---

## 🗂️ Project Structure

```
mesfin-digital-bank/
├── src/
│   ├── app/                        # Next.js App Router pages
│   │   ├── page.tsx                # Landing page
│   │   ├── login/                  # Login portal
│   │   ├── register/               # Registration portal
│   │   ├── forgot-password/        # Password recovery request
│   │   ├── reset-password/         # Password reset with token
│   │   ├── dashboard/              # All user banking pages
│   │   │   ├── page.tsx            # Overview / home
│   │   │   ├── layout.tsx          # Sidebar + mobile nav
│   │   │   ├── transfer/           # Fund transfers page
│   │   │   ├── ledger/             # Transaction history
│   │   │   ├── telebirr/           # Airtime + wallet services
│   │   │   ├── goals/              # Savings goals tracker
│   │   │   ├── beneficiaries/      # Saved contacts
│   │   │   ├── notifications/      # Inbox
│   │   │   └── settings/           # Profile + security + prefs
│   │   └── admin/                  # Admin command center
│   ├── components/
│   │   └── VaultChart.tsx          # Live real-time area chart
│   └── lib/
│       ├── db.ts                   # MySQL connection pool
│       ├── session.ts              # JWT session management
│       └── actions/
│           ├── auth.ts             # Login, Register, Reset password
│           ├── transfer.ts         # Funds transfers + dashboard data
│           ├── services.ts         # Telebirr, Goals, Beneficiaries, etc.
│           ├── admin.ts            # Admin user management
│           └── profile.ts          # User profile fetching
├── legacy-php-backup/              # Original PHP source (reference only)
├── public/                         # Static assets
├── .env.local                      # Environment variables (not committed)
├── package.json
└── README.md
```

---

## 🚀 Getting Started (Local)

**Prerequisites:** Node.js 18+, XAMPP (MySQL)

```bash
# 1. Start MySQL via XAMPP Control Panel

# 2. Import database schema
mysql -u root < legacy-php-backup/database.sql

# 3. Configure environment
cp .env.local .env.local
# Edit .env.local with your credentials

# 4. Install dependencies
npm install

# 5. Start development server
npm run dev
```

**Access:** `http://localhost:3000`

**Default admin login:**
- Email: `admin@mesfinbank.com`
- Password: `password`

---

## 📱 Mobile Access (Same WiFi)

Your local IP: `http://192.168.125.66:3000`

---

## 🌐 Production Deployment (Vercel + Railway)

1. Push this repository to **GitHub**
2. Import to **[Vercel](https://vercel.com)** and connect a **Railway** MySQL database
3. Set all `.env.local` variables in Vercel Environment Variables
4. Update `APP_URL` to your live Vercel domain

---

## 🛠️ Tech Stack

- **Framework**: Next.js 16 (App Router, Turbopack)
- **Language**: TypeScript
- **Styling**: Tailwind CSS v4 + Glassmorphism Design System
- **Animations**: Framer Motion
- **Database**: MySQL (`mysql2`)
- **Auth**: Custom JWT (`jose`) + HttpOnly Cookies
- **Email**: Nodemailer (Gmail SMTP)
- **Charts**: Recharts
- **Icons**: Lucide React

---

## 🔐 Security Notes

- Passwords are hashed with **bcryptjs** (10 rounds)
- Sessions use signed **JWT** stored in **HttpOnly, Secure Cookies**
- Fund transfers use **Atomic DB Transactions** (no partial money loss)
- Admin routes are protected server-side by role checks
- Never commit `.env.local` to Git

---

© 2026 Mesfin Digital Bank. All rights reserved.
