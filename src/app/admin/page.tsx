'use client';

import React, { useState, useEffect, useTransition } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { getAdminStats, adminCreateUser, adminToggleUserStatus } from '@/lib/actions/admin';
import { 
  Users, Shield, TrendingUp, DatabaseZap, Plus, 
  Trash2, Lock, Unlock, X, Loader2, Check, Info,
  ArrowRight, LayoutDashboard, LogOut
} from 'lucide-react';
import { signOut } from '@/lib/actions/auth';
import Link from 'next/link';

export default function AdminPage() {
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [message, setMessage] = useState<{ type: 'error' | 'success', text: string } | null>(null);
  const [isPending, startTransition] = useTransition();

  async function loadStats() {
    const data = await getAdminStats();
    setStats(data);
    setLoading(false);
  }

  useEffect(() => { loadStats(); }, []);

  async function handleCreateUser(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    startTransition(async () => {
      const result = await adminCreateUser(formData);
      if (result?.error) setMessage({ type: 'error', text: result.error });
      else if (result?.success) {
        setMessage({ type: 'success', text: result.success });
        setShowModal(false);
        await loadStats();
      }
    });
  }

  async function handleToggle(userId: number, action: 'freeze' | 'unfreeze' | 'delete') {
    if (action === 'delete' && !confirm('Are you sure you want to permanently delete this user?')) return;
    const result = await adminToggleUserStatus(userId, action);
    if (result?.success) await loadStats();
  }

  const statCards = [
    { label: 'Total Users', value: stats?.totalUsers ?? '—', icon: <Users className="text-violet-400" />, color: 'from-violet-600/20 to-violet-600/5' },
    { label: 'Total Accounts', value: stats?.totalAccounts ?? '—', icon: <Shield className="text-sky-400" />, color: 'from-sky-600/20 to-sky-600/5' },
    { label: 'Total Transactions', value: stats?.totalTransactions ?? '—', icon: <TrendingUp className="text-emerald-400" />, color: 'from-emerald-600/20 to-emerald-600/5' },
    { label: 'Total Assets (ETB)', value: stats ? new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(stats.totalBalance) : '—', icon: <DatabaseZap className="text-amber-400" />, color: 'from-amber-600/20 to-amber-600/5' },
  ];

  return (
    <div className="min-h-screen bg-black text-slate-100">
      {/* Admin Top Bar */}
      <header className="flex items-center justify-between px-6 md:px-10 py-5 border-b border-white/5 glass-panel sticky top-0 z-30">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center">
            <Shield size={16} className="text-black" />
          </div>
          <span className="font-bold tracking-tight">MESFIN<span className="text-amber-500">ADMIN</span></span>
        </div>
        <div className="flex items-center gap-4">
          <Link href="/dashboard" className="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1 transition-colors">
            <LayoutDashboard size={14} /> My Dashboard
          </Link>
          <form action={signOut}>
            <button type="submit" className="text-xs font-bold text-rose-500 hover:text-rose-400 flex items-center gap-1">
              <LogOut size={14} /> Sign Out
            </button>
          </form>
        </div>
      </header>

      <div className="p-6 md:p-10 space-y-10 max-w-7xl mx-auto">
        {/* Page Title */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
          <div>
            <h1 className="text-3xl font-extrabold tracking-tight">Command <span className="text-amber-500">Center</span></h1>
            <p className="text-slate-500 text-sm mt-1">Global bank system administration and oversight.</p>
          </div>
          <button
            onClick={() => { setMessage(null); setShowModal(true); }}
            className="flex items-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-400 text-black rounded-2xl font-bold text-sm transition-colors shadow-lg shadow-amber-500/20"
          >
            <Plus size={18} /> Provision New User
          </button>
        </div>

        {/* Feedback */}
        <AnimatePresence>
          {message && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className={`p-4 rounded-2xl flex items-center gap-2 text-sm font-bold ${
                message.type === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
              }`}
            >
              {message.type === 'success' ? <Check size={16} /> : <Info size={16} />}
              {message.text}
              <button onClick={() => setMessage(null)} className="ml-auto"><X size={14} /></button>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Stats */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-5">
          {statCards.map((card, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.1 }}
              className={`glass-panel p-5 md:p-6 rounded-3xl border-white/5 bg-gradient-to-br ${card.color}`}
            >
              <div className="flex justify-between items-start mb-3">
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{card.label}</span>
                {card.icon}
              </div>
              <div className="text-xl md:text-2xl font-extrabold truncate">{card.value}</div>
            </motion.div>
          ))}
        </div>

        {/* Users Table */}
        <div className="glass-panel rounded-3xl border-white/5 overflow-hidden">
          <div className="p-6 border-b border-white/5 flex justify-between items-center">
            <h2 className="font-bold flex items-center gap-2"><Users size={18} className="text-amber-500" /> Registered Identities</h2>
            <span className="text-xs text-slate-500">{stats?.users?.length || 0} total</span>
          </div>

          {loading ? (
            <div className="p-10 text-center text-slate-500 animate-pulse">Loading system data…</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-white/5">
                    <th className="text-left px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">User</th>
                    <th className="text-left px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell">Email</th>
                    <th className="text-left px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Role</th>
                    <th className="text-right px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Balance</th>
                    <th className="text-right px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {stats?.users?.map((user: any) => (
                    <tr key={user.id} className="border-b border-white/5 hover:bg-white/3 transition-colors">
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 rounded-full bg-gradient-to-tr from-violet-600 to-sky-600 flex items-center justify-center text-[11px] font-bold shrink-0">
                            {user.fullname[0].toUpperCase()}
                          </div>
                          <div>
                            <div className="font-semibold text-sm">{user.fullname}</div>
                            <div className="text-[10px] text-slate-500">@{user.username}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-slate-400 hidden md:table-cell">{user.email}</td>
                      <td className="px-6 py-4 hidden lg:table-cell">
                        <span className={`text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider ${
                          user.role === 'admin' ? 'bg-amber-500/10 text-amber-400' : 'bg-violet-500/10 text-violet-400'
                        }`}>
                          {user.role}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-right font-mono font-bold text-emerald-400">
                        {new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(user.total_balance)} ETB
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center justify-end gap-2">
                          <button
                            onClick={() => handleToggle(user.id, 'freeze')}
                            title="Freeze Accounts"
                            className="p-2 text-sky-500 hover:bg-sky-500/10 rounded-lg transition-colors"
                          >
                            <Lock size={14} />
                          </button>
                          <button
                            onClick={() => handleToggle(user.id, 'unfreeze')}
                            title="Unfreeze Accounts"
                            className="p-2 text-emerald-500 hover:bg-emerald-500/10 rounded-lg transition-colors"
                          >
                            <Unlock size={14} />
                          </button>
                          <button
                            onClick={() => handleToggle(user.id, 'delete')}
                            title="Delete User"
                            className="p-2 text-rose-500 hover:bg-rose-500/10 rounded-lg transition-colors"
                          >
                            <Trash2 size={14} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Create User Modal */}
      <AnimatePresence>
        {showModal && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
          >
            <motion.div
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="glass-panel w-full max-w-md rounded-3xl border-white/10 p-8 shadow-2xl"
            >
              <div className="flex justify-between items-center mb-6">
                <h2 className="font-bold text-lg flex items-center gap-2"><Plus size={18} className="text-amber-500" /> Provision Identity</h2>
                <button onClick={() => setShowModal(false)} className="text-slate-500 hover:text-white p-1">
                  <X size={20} />
                </button>
              </div>

              <form onSubmit={handleCreateUser} className="space-y-4">
                <input name="fullname" required placeholder="Full Legal Name" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-amber-500/50 transition-all" />
                <input name="email" type="email" required placeholder="Email Address" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-amber-500/50 transition-all" />
                <input name="phone" placeholder="Phone (+251...)" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-amber-500/50 transition-all" />
                <input name="password" type="password" required placeholder="Initial Password" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-amber-500/50 transition-all" />
                <div className="grid grid-cols-2 gap-3">
                  <select name="role" className="bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-amber-500/50 transition-all appearance-none">
                    <option value="user" className="bg-slate-900">User</option>
                    <option value="admin" className="bg-slate-900">Admin</option>
                  </select>
                  <input name="initial_balance" type="number" step="0.01" placeholder="Initial Balance" className="bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-amber-500/50 transition-all" />
                </div>

                <button
                  type="submit"
                  disabled={isPending}
                  className="w-full bg-amber-500 hover:bg-amber-400 text-black py-4 rounded-xl font-bold flex items-center justify-center gap-2 transition-all disabled:opacity-50"
                >
                  {isPending ? <Loader2 size={18} className="animate-spin" /> : <><Plus size={18} /> Create Identity</>}
                </button>
              </form>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
