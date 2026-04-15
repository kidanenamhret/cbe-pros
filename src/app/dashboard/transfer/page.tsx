'use client';

import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Send, Wallet, ArrowRight, Loader2, Info } from 'lucide-react';
import { getDashboardData, executeTransfer } from '@/lib/actions/transfer';

export default function TransferPage() {
  const [accounts, setAccounts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    async function load() {
      const data = await getDashboardData();
      if (data) setAccounts(data.accounts || []);
      setLoading(false);
    }
    load();
  }, []);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    setSuccess(null);
    
    // In a real scenario, this is where the 4-digit PIN modal would trigger.
    const formData = new FormData(event.currentTarget);
    const result = await executeTransfer(formData);

    if (result?.error) {
      setError(result.error);
    } else if (result?.success) {
      setSuccess('Transfer executed successfully.');
      (event.target as HTMLFormElement).reset();
      // Reload accounts to show new balances
      const data = await getDashboardData();
      if (data) setAccounts(data.accounts || []);
    }
    
    setSubmitting(false);
  }

  if (loading) {
    return <div className="p-10 text-slate-500 animate-pulse">Initializing Transfer Hub...</div>;
  }

  return (
    <motion.div 
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5 }}
      className="max-w-4xl"
    >
      <div className="mb-10">
        <h1 className="text-3xl font-extrabold tracking-tight">MoonPay <span className="text-violet-500">Transfer</span></h1>
        <p className="text-slate-500 text-sm mt-1 font-medium">Execute secure zero-latency asset transfers.</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {/* Form Section */}
        <div className="md:col-span-2">
          <form onSubmit={handleSubmit} className="glass-panel p-8 rounded-[2rem] border-white/5 shadow-2xl">
            {error && (
              <div className="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-500 text-xs font-bold flex items-center gap-2">
                <Info size={16} /> {error}
              </div>
            )}
            
            {success && (
              <div className="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-500 text-xs font-bold flex items-center gap-2">
                <Info size={16} /> {success}
              </div>
            )}

            <div className="space-y-6">
              <div className="space-y-2">
                <label className="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Source Vault</label>
                <div className="relative group">
                  <Wallet className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-violet-500 transition-colors" size={18} />
                  <select 
                    name="sender_account_id"
                    required
                    className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-violet-500/50 focus:bg-white/10 transition-all text-sm font-medium appearance-none"
                  >
                    <option value="" className="bg-slate-900 text-slate-400">Select an active node...</option>
                    {accounts.map(acc => (
                      <option key={acc.id} value={acc.id} className="bg-slate-900 text-white">
                        {acc.account_type.toUpperCase()} •••• {acc.account_number.slice(-4)} (${acc.balance})
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Destination Address</label>
                <div className="relative group">
                  <Send className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-violet-500 transition-colors" size={18} />
                  <input 
                    type="text" 
                    name="receiver_account"
                    required
                    placeholder="Recipient Account Number"
                    className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-violet-500/50 focus:bg-white/10 transition-all text-sm font-medium"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Amount (ETB)</label>
                  <input 
                    type="number"
                    step="0.01" 
                    name="amount"
                    required
                    placeholder="0.00"
                    className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 px-5 outline-none focus:border-violet-500/50 focus:bg-white/10 transition-all text-xl font-bold font-mono"
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Memo / Purpose</label>
                  <input 
                    type="text" 
                    name="description"
                    placeholder="E.g. Invoice #2026"
                    className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 px-5 outline-none focus:border-violet-500/50 focus:bg-white/10 transition-all text-sm font-medium h-[62px]"
                  />
                </div>
              </div>

              <button 
                type="submit" 
                disabled={submitting}
                className="w-full bg-violet-600 text-white mt-4 py-5 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-violet-500 transition-all shadow-xl shadow-violet-600/20 disabled:opacity-50"
              >
                {submitting ? (
                  <Loader2 className="w-5 h-5 animate-spin" />
                ) : (
                  <>Execute Transfer <ArrowRight size={18} /></>
                )}
              </button>
            </div>
          </form>
        </div>

        {/* Info Module */}
        <div className="space-y-6">
          <div className="glass-panel p-6 rounded-3xl border-white/5 bg-gradient-to-b from-white/5 to-transparent">
            <h3 className="font-bold text-sm mb-4 text-violet-400">Security Parameters</h3>
            <ul className="space-y-3 text-xs text-slate-400 font-medium">
              <li className="flex gap-2"><div className="w-1.5 h-1.5 rounded-full bg-violet-500 mt-1" /> All transfers are endpoint verified.</li>
              <li className="flex gap-2"><div className="w-1.5 h-1.5 rounded-full bg-violet-500 mt-1" /> Transactions exceeding $10k require quantum multisig auth.</li>
              <li className="flex gap-2"><div className="w-1.5 h-1.5 rounded-full bg-violet-500 mt-1" /> Operations are irrecoverable once embedded in the ledger.</li>
            </ul>
          </div>
        </div>
      </div>
    </motion.div>
  );
}
