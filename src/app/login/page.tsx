'use client';

import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { authenticate } from '@/lib/actions/auth';
import { Lock, Mail, ShieldCheck, ArrowRight, Loader2 } from 'lucide-react';
import Link from 'next/link';

export default function LoginPage() {
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError(null);
    
    const formData = new FormData(event.currentTarget);
    const result = await authenticate(formData);

    if (result?.error) {
      setError(result.error);
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-6 bg-black relative overflow-hidden">
      {/* Dynamic Background */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-violet-600/10 rounded-full blur-[120px] pointer-events-none" />
      
      <motion.div 
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className="w-full max-w-md relative z-10"
      >
        <div className="glass-panel p-10 rounded-[2.5rem] border-white/5 shadow-2xl">
          <div className="text-center mb-10">
            <div className="w-16 h-16 bg-gradient-to-br from-violet-600 to-sky-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-violet-600/20">
              <ShieldCheck className="text-white w-8 h-8" />
            </div>
            <h1 className="text-3xl font-extrabold tracking-tight">Identity <span className="text-violet-500">Node</span></h1>
            <p className="text-slate-500 text-sm mt-2 font-medium">Verify your credentials to initialize session.</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-2">
              <label className="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Relay Email</label>
              <div className="relative group">
                <Mail className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-violet-500 transition-colors" size={18} />
                <input 
                  type="email" 
                  name="email"
                  required
                  placeholder="name@relay.node"
                  className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-violet-500/50 focus:bg-white/10 transition-all text-sm font-medium"
                />
              </div>
            </div>

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Access Cipher</label>
                <Link href="/forgot-password" className="text-[10px] font-bold text-violet-400 hover:text-violet-300 transition-colors uppercase tracking-widest">
                  Forgotten?
                </Link>
              </div>
              <div className="relative group">
                <Lock className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-violet-500 transition-colors" size={18} />
                <input 
                  type="password" 
                  name="password"
                  required
                  placeholder="••••••••••••"
                  className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-violet-500/50 focus:bg-white/10 transition-all text-sm font-medium"
                />
              </div>
            </div>

            {error && (
              <motion.div 
                initial={{ opacity: 0, y: -10 }}
                animate={{ opacity: 1, y: 0 }}
                className="p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-500 text-xs font-bold text-center"
              >
                {error}
              </motion.div>
            )}

            <button 
              type="submit" 
              disabled={loading}
              className="w-full bg-white text-black py-4 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-violet-50 transition-all shadow-xl disabled:opacity-50"
            >
              {loading ? (
                <Loader2 className="w-5 h-5 animate-spin" />
              ) : (
                <>Initialize Credentials <ArrowRight size={18} /></>
              )}
            </button>
          </form>

          <div className="mt-8 text-center pt-8 border-t border-white/5">
            <p className="text-slate-500 text-[10px] font-bold tracking-widest uppercase">
              Quantum Secure • End-to-End Encrypted
            </p>
          </div>
        </div>
        
        <p className="text-center mt-6 text-slate-600 text-xs font-medium">
          New to the ecosystem? <a href="/register" className="text-slate-400 hover:text-white transition-colors">Apply for Vault Access</a>
        </p>
      </motion.div>
    </div>
  );
}
