'use client';

import React, { useEffect, useState } from 'react';
import { getDashboardData } from '@/lib/actions/transfer';
import { motion } from 'framer-motion';
import VaultChart from '@/components/VaultChart';
import { 
  ArrowUpRight, 
  ArrowDownLeft, 
  CreditCard, 
  TrendingUp, 
  MoreVertical,
  Zap,
  ShieldCheck,
  History
} from 'lucide-react';

export default function DashboardPage() {
  const [dashboardData, setDashboardData] = useState<any>(null);

  useEffect(() => {
    async function load() {
      const data = await getDashboardData();
      if (data) setDashboardData(data);
    }
    load();
  }, []);

  const accounts = dashboardData?.accounts || [];
  const transactions = dashboardData?.transactions || [];

  // Calculate generic total based on real accounts
  const totalBalance = accounts.reduce((acc: number, curr: any) => acc + parseFloat(curr.balance), 0);
  const formattedTotalBalance = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'ETB' }).format(totalBalance);

  return (
    <motion.div 
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5 }}
      className="space-y-10"
    >
      <div className="flex justify-between items-end">
        <div>
          <h1 className="text-3xl font-extrabold tracking-tight">System <span className="text-violet-500">Overview</span></h1>
          <p className="text-slate-500 text-sm mt-1 font-medium">Monitoring your digital ecosystem assets.</p>
        </div>
        <button className="px-6 py-3 bg-violet-600 hover:bg-violet-700 transition-colors rounded-2xl font-bold text-sm flex items-center gap-2">
          <Zap size={18} fill="currentColor" /> Mint New Asset
        </button>
      </div>

      {/* Stats Quick View */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {[
          { label: 'Total Net Worth', value: formattedTotalBalance, change: '+12.5%', ic: <TrendingUp className="text-emerald-500" /> },
          { label: 'Active Vaults', value: `${accounts.length < 10 ? '0' + accounts.length : accounts.length} Units`, change: 'Stable', ic: <ShieldCheck className="text-sky-500" /> },
          { label: 'Month Volume', value: 'ETB 8,240.22', change: '+5.2%', ic: <Zap className="text-amber-500" /> },
          { label: 'Security Level', value: 'Quantum Link', change: 'Secure', ic: <ShieldCheck className="text-violet-500" /> },
        ].map((stat, i) => (
          <div key={i} className="glass-panel p-6 rounded-3xl border-white/5 hover:border-white/10 transition-all">
            <div className="flex justify-between items-start mb-4">
              <span className="text-slate-400 text-xs font-bold uppercase tracking-wider">{stat.label}</span>
              {stat.ic}
            </div>
            <div className="flex justify-between items-end">
              <span className="text-2xl font-extrabold">{stat.value}</span>
              <span className={`text-[10px] font-bold px-2 py-1 rounded-full ${stat.change.includes('+') ? 'bg-emerald-500/10 text-emerald-500' : 'bg-white/10 text-slate-400'}`}>
                {stat.change}
              </span>
            </div>
          </div>
        ))}
      </div>

      <div className="w-full">
        <VaultChart />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
        {/* Cards Section */}
        <div className="lg:col-span-2 space-y-6">
          <div className="flex justify-between items-center">
            <h2 className="text-lg font-bold flex items-center gap-2">
              <CreditCard size={18} className="text-violet-500" /> Active Nodes
            </h2>
            <button className="text-violet-400 text-xs font-bold hover:underline">View All Units</button>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {accounts.length === 0 ? (
              <div className="p-8 text-center text-slate-500 col-span-2">No active vaults found.</div>
            ) : accounts.map((card: any, i: number) => {
              const bgGradient = i % 2 === 0 ? 'from-violet-600 to-indigo-600' : 'from-sky-600 to-cyan-600';
              const formattedBal = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(card.balance);

              return (
                <motion.div 
                  key={card.id || i}
                  whileHover={{ scale: 1.02 }}
                  className={`h-52 rounded-[2.5rem] p-8 flex flex-col justify-between relative overflow-hidden group shadow-2xl bg-gradient-to-br ${bgGradient}`}
                >
                  <div className="absolute top-[-20%] left-[-20%] w-[100%] h-[100%] bg-white/10 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700" />
                  <div className="flex justify-between items-start relative z-10">
                    <div className="w-12 h-8 bg-white/20 rounded-lg backdrop-blur-md border border-white/10" />
                    <span className="text-[10px] font-bold tracking-widest opacity-80 uppercase">{card.currency} Relay</span>
                  </div>
                  
                  <div className="relative z-10">
                    <span className="text-[10px] opacity-70 font-bold block mb-1">{card.account_type}</span>
                    <div className="text-3xl font-extrabold tracking-tighter">{card.currency} {formattedBal}</div>
                  </div>

                  <div className="flex justify-between items-end relative z-10">
                    <span className="font-mono text-sm tracking-[0.2em] opacity-80">•••• {card.account_number?.slice(-4) || 'XXXX'}</span>
                    <div className="flex -space-x-3">
                      <div className="w-8 h-8 rounded-full bg-orange-500/80 mix-blend-screen" />
                      <div className="w-8 h-8 rounded-full bg-red-500/80 mix-blend-screen" />
                    </div>
                  </div>
                </motion.div>
              );
            })}
          </div>
        </div>

        {/* Transactions Section */}
        <div className="space-y-6">
          <div className="flex justify-between items-center">
            <h2 className="text-lg font-bold flex items-center gap-2">
              <History size={18} className="text-violet-500" /> System Ledger
            </h2>
            <MoreVertical size={18} className="text-slate-500 cursor-pointer" />
          </div>

          <div className="glass-panel rounded-3xl p-6 border-white/5 space-y-4">
            {transactions.length === 0 ? (
              <div className="text-center text-slate-500 py-4">No recent ledger entries.</div>
            ) : transactions.map((tx: any, i: number) => {
              const isCredit = tx.direction === 'credit';
              const formattedAmt = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'ETB' }).format(tx.amount);
              
              return (
                <div key={tx.id || i} className="flex items-center justify-between group cursor-pointer hover:bg-white/5 p-2 rounded-2xl transition-all">
                  <div className="flex items-center gap-4">
                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${isCredit ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500'}`}>
                      {isCredit ? <ArrowDownLeft size={18} /> : <ArrowUpRight size={18} />}
                    </div>
                    <div>
                      <div className="text-sm font-bold">{tx.description || tx.type}</div>
                      <div className="text-[10px] text-slate-500 font-medium">{new Date(tx.created_at).toLocaleDateString()} • {tx.status}</div>
                    </div>
                  </div>
                  <div className={`text-sm font-bold ${isCredit ? 'text-emerald-500' : 'text-slate-200'}`}>
                    {isCredit ? '+' : '-'}{formattedAmt}
                  </div>
                </div>
              );
            })}
            <button className="w-full py-4 text-[10px] font-bold tracking-widest text-slate-500 hover:text-white transition-colors border-t border-white/5 mt-2">
              EXAMINE FULL LEDGER
            </button>
          </div>
        </div>
      </div>
    </motion.div>
  );
}
