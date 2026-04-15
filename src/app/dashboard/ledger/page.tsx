'use client';
import React, { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { getTransactionHistory } from '@/lib/actions/services';
import { ArrowUpRight, ArrowDownLeft, Search, Filter } from 'lucide-react';

export default function LedgerPage() {
  const [txns, setTxns] = useState<any[]>([]);
  const [filtered, setFiltered] = useState<any[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getTransactionHistory(100).then(data => {
      setTxns(data || []);
      setFiltered(data || []);
      setLoading(false);
    });
  }, []);

  useEffect(() => {
    if (!search) { setFiltered(txns); return; }
    const q = search.toLowerCase();
    setFiltered(txns.filter(t => 
      (t.description || '').toLowerCase().includes(q) ||
      (t.type || '').toLowerCase().includes(q) ||
      (t.status || '').toLowerCase().includes(q)
    ));
  }, [search, txns]);

  return (
    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="max-w-5xl space-y-8">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 className="text-3xl font-extrabold tracking-tight">Transaction <span className="text-violet-500">Ledger</span></h1>
          <p className="text-slate-500 text-sm mt-1">Complete audit trail of your financial activity.</p>
        </div>
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" size={16} />
          <input
            value={search}
            onChange={e => setSearch(e.target.value)}
            placeholder="Search transactions..."
            className="bg-white/5 border border-white/5 rounded-xl py-2.5 pl-9 pr-4 text-sm outline-none focus:border-violet-500/50 w-64"
          />
        </div>
      </div>

      <div className="glass-panel rounded-3xl border-white/5 overflow-hidden">
        {loading ? (
          <div className="p-12 text-center text-slate-500 animate-pulse">Loading ledger…</div>
        ) : filtered.length === 0 ? (
          <div className="p-12 text-center text-slate-500">No transactions found.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-white/5">
                  <th className="text-left px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Transaction</th>
                  <th className="text-left px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell">Type</th>
                  <th className="text-left px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Date</th>
                  <th className="text-center px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                  <th className="text-right px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map((tx: any) => {
                  const isCredit = tx.direction === 'credit';
                  const fmt = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(tx.amount);
                  return (
                    <tr key={tx.id} className="border-b border-white/5 hover:bg-white/3 transition-all">
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${isCredit ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500'}`}>
                            {isCredit ? <ArrowDownLeft size={16} /> : <ArrowUpRight size={16} />}
                          </div>
                          <span className="font-semibold">{tx.description || tx.type}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-slate-400 capitalize hidden md:table-cell">{tx.type}</td>
                      <td className="px-6 py-4 text-slate-400 hidden lg:table-cell">{new Date(tx.created_at).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                      <td className="px-6 py-4 text-center">
                        <span className={`text-[10px] font-bold px-2 py-1 rounded-full ${tx.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'}`}>
                          {tx.status}
                        </span>
                      </td>
                      <td className={`px-6 py-4 text-right font-mono font-bold ${isCredit ? 'text-emerald-400' : 'text-slate-200'}`}>
                        {isCredit ? '+' : '-'} {fmt} ETB
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </motion.div>
  );
}
