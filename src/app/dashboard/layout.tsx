'use client';

import React, { useState } from 'react';
import { 
  LayoutDashboard, 
  Send, 
  Wallet, 
  History, 
  Users, 
  Settings, 
  LogOut, 
  Bell, 
  Search,
  Menu,
  X,
  Zap,
  Target,
  HeadphonesIcon
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { signOut } from '@/lib/actions/auth';

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const [mobileOpen, setMobileOpen] = useState(false);

  const menuItems = [
    { icon: <LayoutDashboard size={20} />, label: 'Overview', href: '/dashboard' },
    { icon: <Send size={20} />, label: 'Transfer Hub', href: '/dashboard/transfer' },
    { icon: <Wallet size={20} />, label: 'Digital Vaults', href: '/dashboard/vaults' },
    { icon: <History size={20} />, label: 'Ledger', href: '/dashboard/ledger' },
    { icon: <Zap size={20} />, label: 'Telebirr', href: '/dashboard/telebirr' },
    { icon: <Target size={20} />, label: 'Savings Goals', href: '/dashboard/goals' },
    { icon: <Users size={20} />, label: 'Beneficiaries', href: '/dashboard/beneficiaries' },
    { icon: <Bell size={20} />, label: 'Notifications', href: '/dashboard/notifications' },
    { icon: <Settings size={20} />, label: 'Settings', href: '/dashboard/settings' },
  ];

  const SidebarContent = () => (
    <>
      <div className="flex items-center gap-3 mb-10 px-2">
        <div className="w-8 h-8 bg-violet-600 rounded-lg flex items-center justify-center">
          <span className="font-bold text-white text-sm">M</span>
        </div>
        <span className="font-bold tracking-tight text-lg">MESFIN<span className="text-violet-500">BANK</span></span>
      </div>

      <nav className="flex-1 space-y-2">
        {menuItems.map((item, i) => {
          const isActive = pathname === item.href;
          return (
            <Link
              key={i}
              href={item.href}
              onClick={() => setMobileOpen(false)}
              className={`flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-300 ${
                isActive 
                  ? 'bg-violet-600/10 text-violet-400 shadow-[inset_0_0_10px_rgba(139,92,246,0.1)]' 
                  : 'text-slate-500 hover:text-slate-200'
              }`}
            >
              <span className={isActive ? 'text-violet-400' : ''}>{item.icon}</span>
              <span className="text-sm font-semibold">{item.label}</span>
            </Link>
          );
        })}
      </nav>

      <form action={signOut}>
        <button type="submit" className="flex items-center gap-4 px-4 py-3 text-rose-500 hover:bg-rose-500/5 rounded-xl transition-all duration-300 mt-auto w-full">
          <LogOut size={20} />
          <span className="text-sm font-semibold">Sign Out</span>
        </button>
      </form>
    </>
  );

  return (
    <div className="flex h-screen bg-black text-slate-100 overflow-hidden">
      
      {/* Desktop Sidebar */}
      <aside className="hidden md:flex w-72 glass-panel border-r border-white/5 flex-col p-6 m-4 rounded-3xl">
        <SidebarContent />
      </aside>

      {/* Mobile Overlay Sidebar */}
      <AnimatePresence>
        {mobileOpen && (
          <>
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMobileOpen(false)}
              className="fixed inset-0 bg-black/60 z-40 md:hidden backdrop-blur-sm"
            />
            <motion.aside
              initial={{ x: '-100%' }}
              animate={{ x: 0 }}
              exit={{ x: '-100%' }}
              transition={{ type: 'spring', stiffness: 300, damping: 30 }}
              className="fixed top-0 left-0 h-full w-72 glass-panel border-r border-white/10 flex flex-col p-6 z-50 md:hidden rounded-r-3xl"
            >
              <button
                onClick={() => setMobileOpen(false)}
                className="absolute top-4 right-4 text-slate-500 hover:text-white p-2"
              >
                <X size={20} />
              </button>
              <SidebarContent />
            </motion.aside>
          </>
        )}
      </AnimatePresence>

      {/* Main Container */}
      <main className="flex-1 flex flex-col overflow-hidden relative">
        {/* Top Header */}
        <header className="flex items-center justify-between px-4 md:px-10 py-4 md:py-6 border-b border-white/5">
          <div className="flex items-center gap-3">
            {/* Mobile menu toggle */}
            <button
              onClick={() => setMobileOpen(true)}
              className="md:hidden p-2 text-slate-400 hover:text-white transition-colors"
            >
              <Menu size={22} />
            </button>

            {/* Mobile brand */}
            <span className="md:hidden font-bold tracking-tight text-base">MESFIN<span className="text-violet-500">BANK</span></span>

            {/* Desktop search */}
            <div className="hidden md:flex items-center bg-white/5 rounded-full px-4 py-2 border border-white/5 w-96">
              <Search size={18} className="text-slate-500" />
              <input 
                type="text" 
                placeholder="Search assets or transactions..." 
                className="bg-transparent border-none outline-none px-3 text-sm flex-1 text-slate-300"
              />
            </div>
          </div>

          <div className="flex items-center gap-4">
            <button className="relative text-slate-400 hover:text-white transition-colors">
              <Bell size={20} />
              <span className="absolute -top-1 -right-1 w-2 h-2 bg-rose-500 rounded-full border-2 border-black" />
            </button>
            <div className="w-9 h-9 rounded-full bg-gradient-to-tr from-violet-600 to-sky-600 p-[2px] cursor-pointer">
              <div className="w-full h-full rounded-full bg-black flex items-center justify-center font-bold text-xs">
                M
              </div>
            </div>
          </div>
        </header>

        {/* Dynamic Content */}
        <section className="flex-1 overflow-y-auto p-4 md:p-10">
          {children}
        </section>
      </main>
    </div>
  );
}
