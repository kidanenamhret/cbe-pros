'use client';
import React, { useEffect, useState, useTransition } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { getBeneficiaries, addBeneficiary } from '@/lib/actions/services';
import { Users, Plus, X, Loader2, Check, Info, Send } from 'lucide-react';
import Link from 'next/link';

export default function BeneficiariesPage() {
  const [beneficiaries, setBeneficiaries] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [msg, setMsg] = useState<{ type: string; text: string } | null>(null);
  const [isPending, startTransition] = useTransition();

  async function load() {
    const data = await getBeneficiaries();
    setBeneficiaries(data);
    setLoading(false);
  }
  useEffect(() => { load(); }, []);

  async function handleAdd(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    startTransition(async () => {
      const result = await addBeneficiary(formData);
      if (result?.error) setMsg({ type: 'error', text: result.error });
      else { setMsg({ type: 'success', text: `${result.name} added!` }); setShowModal(false); load(); }
    });
  }

  return (
    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="max-w-4xl space-y-8">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 className="text-3xl font-extrabold tracking-tight">Saved <span className="text-violet-500">Contacts</span></h1>
          <p className="text-slate-500 text-sm mt-1">Quick-access destinations for your transfers.</p>
        </div>
        <button onClick={() => { setMsg(null); setShowModal(true); }} className="flex items-center gap-2 px-6 py-3 bg-violet-600 hover:bg-violet-500 rounded-2xl font-bold text-sm transition-colors">
          <Plus size={16} /> Add Beneficiary
        </button>
      </div>

      <AnimatePresence>
        {msg && (
          <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }}
            className={`p-4 rounded-2xl flex items-center gap-2 text-sm font-bold ${msg.type === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}`}>
            {msg.type === 'success' ? <Check size={16} /> : <Info size={16} />} {msg.text}
            <button onClick={() => setMsg(null)} className="ml-auto"><X size={14} /></button>
          </motion.div>
        )}
      </AnimatePresence>

      {loading ? <div className="text-slate-500 animate-pulse p-10 text-center">Loading contacts…</div> : beneficiaries.length === 0 ? (
        <div className="glass-panel rounded-3xl border-white/5 p-16 text-center">
          <Users size={40} className="text-slate-600 mx-auto mb-4" />
          <p className="text-slate-400 font-semibold">No saved contacts yet.</p>
          <p className="text-slate-600 text-sm mt-2">Add beneficiaries to speed up future transfers.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          {beneficiaries.map((b: any) => (
            <div key={b.id} className="glass-panel rounded-2xl border-white/5 p-5 flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-full bg-gradient-to-tr from-violet-600 to-sky-600 flex items-center justify-center font-bold text-lg">
                  {(b.beneficiary_name || b.nickname || '?')[0].toUpperCase()}
                </div>
                <div>
                  <div className="font-bold">{b.nickname || b.beneficiary_name}</div>
                  <div className="text-xs text-slate-500 font-mono">•••• {b.beneficiary_account.slice(-4)}</div>
                </div>
              </div>
              <Link href={`/dashboard/transfer?to=${b.beneficiary_account}`} className="p-2 bg-violet-600/10 text-violet-400 hover:bg-violet-600/20 rounded-xl transition-colors">
                <Send size={16} />
              </Link>
            </div>
          ))}
        </div>
      )}

      <AnimatePresence>
        {showModal && (
          <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <motion.div initial={{ scale: 0.9 }} animate={{ scale: 1 }} exit={{ scale: 0.9 }} className="glass-panel w-full max-w-sm rounded-3xl border-white/10 p-8">
              <div className="flex justify-between items-center mb-6">
                <h2 className="font-bold text-lg">Add Beneficiary</h2>
                <button onClick={() => setShowModal(false)} className="text-slate-500 hover:text-white"><X size={20} /></button>
              </div>
              <form onSubmit={handleAdd} className="space-y-4">
                <input name="account_number" required placeholder="Account Number" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50 transition-all font-mono" />
                <input name="nickname" placeholder="Nickname (optional)" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50 transition-all" />
                <button type="submit" disabled={isPending} className="w-full bg-violet-600 hover:bg-violet-500 text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-50">
                  {isPending ? <Loader2 size={16} className="animate-spin" /> : <><Plus size={16} /> Save Contact</>}
                </button>
              </form>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  );
}
