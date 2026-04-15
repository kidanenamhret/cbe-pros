'use client';

import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { requestPasswordReset } from '@/lib/actions/auth';
import { Mail, KeyRound, ArrowRight, Loader2, Info } from 'lucide-react';
import Link from 'next/link';

export default function ForgotPasswordPage() {
  const [message, setMessage] = useState<{ type: 'error' | 'success', text: string, url?: string } | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setMessage(null);
    
    const formData = new FormData(event.currentTarget);
    const result = await requestPasswordReset(formData);

    if (result?.error) {
      setMessage({ type: 'error', text: result.error });
    } else if (result?.success) {
      setMessage({ type: 'success', text: result.success, url: result.previewUrl });
    }
    setLoading(false);
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-6 bg-black relative overflow-hidden">
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-sky-600/10 rounded-full blur-[120px] pointer-events-none" />
      
      <motion.div 
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className="w-full max-w-md relative z-10"
      >
        <div className="glass-panel p-10 rounded-[2.5rem] border-white/5 shadow-2xl">
          <div className="text-center mb-10">
            <div className="w-16 h-16 bg-gradient-to-br from-violet-600 to-sky-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-sky-600/20">
              <KeyRound className="text-white w-8 h-8" />
            </div>
            <h1 className="text-3xl font-extrabold tracking-tight">Recovery <span className="text-sky-500">Node</span></h1>
            <p className="text-slate-500 text-sm mt-2 font-medium">Re-establish connection to your vault.</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-2">
              <label className="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Relay Email</label>
              <div className="relative group">
                <Mail className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-sky-500 transition-colors" size={18} />
                <input 
                  type="email" 
                  name="email"
                  required
                  placeholder="name@relay.node"
                  className="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 outline-none focus:border-sky-500/50 focus:bg-white/10 transition-all text-sm font-medium"
                />
              </div>
            </div>

            {message && (
              <motion.div 
                initial={{ opacity: 0, y: -10 }}
                animate={{ opacity: 1, y: 0 }}
                className={`p-4 border rounded-xl text-xs font-bold text-center ${
                  message.type === 'error' 
                    ? 'bg-rose-500/10 border-rose-500/20 text-rose-500' 
                    : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500 flex flex-col gap-2'
                }`}
              >
                <div>{message.text}</div>
                {message.url && (
                  <a href={message.url} target="_blank" className="underline text-sky-400">View Prototype Email</a>
                )}
              </motion.div>
            )}

            <button 
              type="submit" 
              disabled={loading}
              className="w-full bg-white text-black py-4 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-sky-50 transition-all shadow-xl disabled:opacity-50"
            >
              {loading ? (
                <Loader2 className="w-5 h-5 animate-spin" />
              ) : (
                <>Request Access Link <ArrowRight size={18} /></>
              )}
            </button>
          </form>

          <div className="mt-8 text-center pt-8 border-t border-white/5">
                 <Link href="/login" className="text-slate-500 text-[10px] font-bold tracking-widest uppercase hover:text-white transition-colors">
              Remembered your cipher? Return to Login
            </Link>
          </div>
        </div>
      </motion.div>
    </div>
  );
}
