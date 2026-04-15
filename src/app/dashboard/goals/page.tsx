'use client';
import React, { useEffect, useState, useTransition } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { getGoals, createGoal, contributeToGoal } from '@/lib/actions/services';
import { getDashboardData } from '@/lib/actions/transfer';
import { Target, Plus, X, Loader2, Coins } from 'lucide-react';

export default function GoalsPage() {
  const [goals, setGoals] = useState<any[]>([]);
  const [accounts, setAccounts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState<null | 'new' | { id: number; title: string }>( null);
  const [msg, setMsg] = useState<string | null>(null);
  const [isPending, startTransition] = useTransition();

  async function load() {
    const [g, d] = await Promise.all([getGoals(), getDashboardData()]);
    setGoals(g);
    setAccounts(d?.accounts || []);
    setLoading(false);
  }
  useEffect(() => { load(); }, []);

  async function handleNewGoal(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    startTransition(async () => {
      const result = await createGoal(formData);
      if (result?.success) { setModal(null); load(); }
      else setMsg(result?.error || 'Error');
    });
  }

  async function handleContribute(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    startTransition(async () => {
      const result = await contributeToGoal(formData);
      if (result?.success) { setModal(null); load(); }
      else setMsg(result?.error || 'Error');
    });
  }

  return (
    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="max-w-5xl space-y-8">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 className="text-3xl font-extrabold tracking-tight">Savings <span className="text-violet-500">Goals</span></h1>
          <p className="text-slate-500 text-sm mt-1">Visualize your dreams and track your progress.</p>
        </div>
        <button onClick={() => { setMsg(null); setModal('new'); }} className="flex items-center gap-2 px-6 py-3 bg-violet-600 hover:bg-violet-500 rounded-2xl font-bold text-sm">
          <Plus size={16} /> New Goal
        </button>
      </div>

      {loading ? <div className="text-center text-slate-500 animate-pulse p-12">Loading goals…</div> : goals.length === 0 ? (
        <div className="glass-panel rounded-3xl border-white/5 p-16 text-center">
          <Target size={48} className="text-slate-600 mx-auto mb-4" />
          <p className="text-slate-400 font-semibold">No savings goals yet.</p>
          <p className="text-slate-500 text-sm mt-1">Setting goals is the first step to financial freedom.</p>
          <button onClick={() => setModal('new')} className="mt-6 px-6 py-3 bg-violet-600 rounded-2xl font-bold text-sm">Create First Goal</button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          {goals.map((goal: any) => {
            const progress = goal.target_amount > 0 ? Math.min(100, (goal.current_amount / goal.target_amount) * 100) : 0;
            return (
              <div key={goal.id} className="glass-panel rounded-3xl border-white/5 p-6 space-y-4">
                <div className="flex justify-between items-start">
                  <span className={`text-[10px] font-bold px-2 py-1 rounded-full ${goal.status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-500/10 text-slate-400'}`}>{goal.status}</span>
                  <span className="text-violet-400 font-bold text-sm">{progress.toFixed(0)}%</span>
                </div>
                <h3 className="font-extrabold text-lg">{goal.title}</h3>
                <div className="h-2 bg-white/5 rounded-full overflow-hidden">
                  <div className="h-full bg-gradient-to-r from-violet-600 to-sky-500 rounded-full transition-all duration-1000" style={{ width: `${progress}%` }} />
                </div>
                <div className="flex justify-between text-sm">
                  <div>
                    <div className="font-bold">{parseFloat(goal.current_amount).toFixed(2)} ETB</div>
                    <div className="text-[10px] text-slate-500">Saved</div>
                  </div>
                  <div className="text-right">
                    <div className="font-bold">{parseFloat(goal.target_amount).toFixed(2)} ETB</div>
                    <div className="text-[10px] text-slate-500">Target</div>
                  </div>
                </div>
                <button onClick={() => { setMsg(null); setModal({ id: goal.id, title: goal.title }); }}
                  className="w-full py-2.5 bg-white/5 hover:bg-violet-600/20 text-violet-400 rounded-2xl text-sm font-bold flex items-center justify-center gap-2 transition-all">
                  <Coins size={14} /> Add Savings
                </button>
              </div>
            );
          })}
        </div>
      )}

      {/* Modals */}
      <AnimatePresence>
        {modal && (
          <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <motion.div initial={{ scale: 0.9 }} animate={{ scale: 1 }} exit={{ scale: 0.9 }} className="glass-panel w-full max-w-sm rounded-3xl border-white/10 p-8">
              <div className="flex justify-between items-center mb-6">
                <h2 className="font-bold text-lg">{modal === 'new' ? 'Create Goal' : `Add to "${(modal as any).title}"`}</h2>
                <button onClick={() => setModal(null)} className="text-slate-500 hover:text-white"><X size={20} /></button>
              </div>
              {msg && <div className="mb-4 p-3 bg-rose-500/10 text-rose-400 rounded-xl text-xs font-bold">{msg}</div>}
              
              {modal === 'new' ? (
                <form onSubmit={handleNewGoal} className="space-y-4">
                  <input name="title" required placeholder="e.g. New House, Dream Car" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50" />
                  <input name="target" type="number" min="100" step="100" required placeholder="Target Amount (ETB)" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50" />
                  <button type="submit" disabled={isPending} className="w-full bg-violet-600 py-3 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-50">
                    {isPending ? <Loader2 size={16} className="animate-spin" /> : <><Plus size={16} /> Start Goal</>}
                  </button>
                </form>
              ) : (
                <form onSubmit={handleContribute} className="space-y-4">
                  <input type="hidden" name="goal_id" value={(modal as any).id} />
                  <select name="source_account" required className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50 appearance-none">
                    <option value="" className="bg-slate-900">Select source account…</option>
                    {accounts.map(a => <option key={a.account_number} value={a.account_number} className="bg-slate-900">{a.account_type} •••• {a.account_number.slice(-4)} ({parseFloat(a.balance).toFixed(2)} ETB)</option>)}
                  </select>
                  <input name="amount" type="number" min="1" step="1" required placeholder="Amount to contribute" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50" />
                  <button type="submit" disabled={isPending} className="w-full bg-violet-600 py-3 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-50">
                    {isPending ? <Loader2 size={16} className="animate-spin" /> : <><Coins size={16} /> Push Savings</>}
                  </button>
                </form>
              )}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  );
}
