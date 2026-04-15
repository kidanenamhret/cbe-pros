'use client';

import React from 'react';
import { motion } from 'framer-motion';
import { 
  ShieldCheck, 
  ArrowRight, 
  Zap, 
  Globe, 
  Wallet, 
  ChevronRight,
  TrendingUp,
  Lock
} from 'lucide-react';

export default function LandingPage() {
  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.2
      }
    }
  };

  const itemVariants = {
    hidden: { y: 20, opacity: 0 },
    visible: {
      y: 0,
      opacity: 1,
      transition: {
        duration: 0.6,
        ease: "easeOut"
      }
    }
  };

  return (
    <div className="relative min-height-screen overflow-hidden">
      {/* Background Orbs */}
      <div className="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] bg-violet-600/20 rounded-full blur-[120px] pointer-events-none" />
      <div className="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] bg-sky-600/20 rounded-full blur-[120px] pointer-events-none" />

      {/* Navigation */}
      <nav className="flex items-center justify-between px-8 py-6 max-w-7xl mx-auto relative z-10">
        <div className="flex items-center gap-2">
          <div className="w-10 h-10 bg-gradient-to-br from-violet-500 to-sky-500 rounded-xl flex items-center justify-center shadow-lg shadow-violet-500/20">
            <ShieldCheck className="text-white w-6 h-6" />
          </div>
          <span className="text-xl font-bold tracking-tight">MESFIN<span className="text-violet-400">BANK</span></span>
        </div>
        
        <div className="hidden md:flex items-center gap-8 text-sm font-medium text-slate-400">
          <a href="#" className="hover:text-white transition-colors">Our Ecosystem</a>
          <a href="#" className="hover:text-white transition-colors">Security</a>
          <a href="#" className="hover:text-white transition-colors">Enterprise</a>
        </div>

        <button className="glass-button px-6 py-2 rounded-full text-sm font-semibold flex items-center gap-2">
          Dashboard <ChevronRight className="w-4 h-4" />
        </button>
      </nav>

      {/* Hero Section */}
      <main className="max-w-7xl mx-auto px-8 pt-20 pb-32 relative z-10">
        <motion.div 
          className="text-center max-w-4xl mx-auto"
          variants={containerVariants}
          initial="hidden"
          animate="visible"
        >
          <motion.div variants={itemVariants} className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 text-xs font-semibold text-violet-300 mb-8">
            <Zap className="w-3 h-3 fill-current" />
            V4.0 QUANTUM ENGINE NOW LIVE
          </motion.div>

          <motion.h1 variants={itemVariants} className="text-6xl md:text-8xl font-extrabold mb-8 tracking-tighter leading-[1.1]">
            Modern Banking for the <span className="gradient-text">Next Generation.</span>
          </motion.h1>

          <motion.p variants={itemVariants} className="text-lg md:text-xl text-slate-400 mb-12 max-w-2xl mx-auto leading-relaxed">
            Experience the fusion of high-frequency finance and aesthetic intelligence. 
            Secure, boundless, and architected for the future of digital assets.
          </motion.p>

          <motion.div variants={itemVariants} className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <button 
              onClick={() => window.location.href = '/login'}
              className="w-full sm:w-auto px-8 py-4 bg-white text-black rounded-full font-bold flex items-center justify-center gap-2 hover:bg-violet-50 transition-colors"
            >
              Open App <ArrowRight className="w-5 h-5" />
            </button>
            <button 
              onClick={() => window.location.href = '#'}
              className="w-full sm:w-auto px-8 py-4 glass-button rounded-full font-bold flex items-center justify-center gap-2"
            >
              System Architecture
            </button>
          </motion.div>

        </motion.div>

        {/* Feature Cards */}
        <div className="grid md:grid-cols-3 gap-6 mt-32">
          {[
            { 
              icon: <Globe className="w-6 h-6 text-sky-400" />, 
              title: "Global Liquidity", 
              desc: "Instant cross-border settlement using our localized moon-pay corridors." 
            },
            { 
              icon: <TrendingUp className="w-6 h-6 text-violet-400" />, 
              title: "Smart Ledgers", 
              desc: "AI-driven transaction analysis and real-time forensic tracking." 
            },
            { 
              icon: <Lock className="w-6 h-6 text-amber-400" />, 
              title: "Quantum Security", 
              desc: "End-to-end encrypted vaults with multi-factor biometric auth." 
            }
          ].map((feature, i) => (
            <motion.div 
              key={i}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.1 }}
              className="glass-panel p-8 group hover:border-violet-500/30 transition-colors"
            >
              <div className="mb-4">{feature.icon}</div>
              <h3 className="text-xl font-bold mb-2">{feature.title}</h3>
              <p className="text-slate-400 text-sm leading-relaxed">{feature.desc}</p>
            </motion.div>
          ))}
        </div>
      </main>

      {/* Footer Branding */}
      <footer className="border-t border-white/5 py-8 text-center text-slate-500 text-sm">
        &copy; 2026 Mesfin Digital Bank. All rights reserved. Built with Next.js 14.
      </footer>
    </div>
  );
}
