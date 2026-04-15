'use client';
import React, { useEffect, useState, useTransition } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { telebirrService } from '@/lib/actions/services';
import { getDashboardData } from '@/lib/actions/transfer';
import { Zap, Smartphone, ArrowRight, Loader2, Check, Info } from 'lucide-react';

export default function TelebirrPage() {
  const [accounts, setAccounts] = useState<any[]>([]);
  const [activeTab, setActiveTab] = useState<'airtime' | 'wallet'>('airtime');
  const [msg, setMsg] = useState<{ type: string; text: string } | null>(null);
  const [isPending, startTransition] = useTransition();

  useEffect(() => {
    getDashboardData().then(d => setAccounts(d?.accounts || []));
  }, []);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    formData.set('type', activeTab);
    startTransition(async () => {
      const result = await telebirrService(formData);
      if (result?.error) setMsg({ type: 'error', text: result.error });
      else { setMsg({ type: 'success', text: result.message || 'Operation successful' }); (e.target as HTMLFormElement).reset(); }
    });
  }

  return (
    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="max-w-2xl space-y-8">
      <div>
        <h1 className="text-3xl font-extrabold tracking-tight">Telebirr <span className="text-violet-500">Services</span></h1>
        <p className="text-slate-500 text-sm mt-1">Buy airtime and transfer to Telebirr wallets.</p>
      </div>

      <div className="flex gap-2 glass-panel p-1.5 rounded-2xl border-white/5 w-fit">
        {[{ key: 'airtime', label: 'Buy Airtime', icon: <Smartphone size={16} /> }, { key: 'wallet', label: 'Send to Wallet', icon: <Zap size={16} /> }].map(tab => (
          <button key={tab.key} onClick={() => { setActiveTab(tab.key as any); setMsg(null); }}
            className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all ${activeTab === tab.key ? 'bg-violet-600 text-white shadow-lg' : 'text-slate-400 hover:text-white'}`}>
            {tab.icon} {tab.label}
          </button>
        ))}
      </div>

      <AnimatePresence>
        {msg && (
          <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }}
            className={`p-4 rounded-2xl text-sm font-bold flex items-center gap-2 ${msg.type === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}`}>
            {msg.type === 'success' ? <Check size={16} /> : <Info size={16} />} {msg.text}
          </motion.div>
        )}
      </AnimatePresence>

      <form onSubmit={handleSubmit} className="glass-panel rounded-3xl border-white/5 p-8 space-y-6">
        <div className="space-y-2">
          <label className="text-xs font-bold text-slate-400 uppercase tracking-widest">Source Account</label>
          <select name="account_id" required className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 px-4 outline-none focus:border-violet-500/50 text-sm appearance-none">
            <option value="" className="bg-slate-900">Select account…</option>
            {accounts.map(a => <option key={a.id} value={a.id} className="bg-slate-900">{a.account_type.toUpperCase()} •••• {a.account_number.slice(-4)} ({parseFloat(a.balance).toFixed(2)} ETB)</option>)}
          </select>
        </div>
        <div className="space-y-2">
          <label className="text-xs font-bold text-slate-400 uppercase tracking-widest">{activeTab === 'airtime' ? 'Phone Number' : 'Telebirr Number'}</label>
          <input name="phone" required placeholder="09XX XXX XXXX" className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 px-4 outline-none focus:border-violet-500/50 text-sm" />
        </div>
        <div className="space-y-2">
          <label className="text-xs font-bold text-slate-400 uppercase tracking-widest">Amount (ETB)</label>
          <input name="amount" type="number" step="1" min={activeTab === 'airtime' ? 5 : 10} required placeholder={activeTab === 'airtime' ? 'Min 5 ETB' : 'Min 10 ETB'} className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 px-4 outline-none focus:border-violet-500/50 text-2xl font-bold font-mono" />
        </div>
        <button type="submit" disabled={isPending} className="w-full bg-violet-600 hover:bg-violet-500 py-5 rounded-2xl font-bold flex items-center justify-center gap-2 disabled:opacity-50 shadow-lg shadow-violet-600/20">
          {isPending ? <Loader2 size={18} className="animate-spin" /> : <>{activeTab === 'airtime' ? 'Recharge Airtime' : 'Transfer to Wallet'} <ArrowRight size={18} /></>}
        </button>
      </form>
    </motion.div>
  );
}
