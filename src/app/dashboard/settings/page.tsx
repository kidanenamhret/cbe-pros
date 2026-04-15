'use client';
import React, { useEffect, useState, useTransition } from 'react';
import { motion } from 'framer-motion';
import { getUserProfile } from '@/lib/actions/profile';
import { updateProfile, changePassword } from '@/lib/actions/services';
import { User, Lock, Bell, Shield, Check, Info, Loader2 } from 'lucide-react';

export default function SettingsPage() {
  const [profile, setProfile] = useState<any>(null);
  const [activeTab, setActiveTab] = useState('profile');
  const [msg, setMsg] = useState<{ type: string; text: string } | null>(null);
  const [isPending, startTransition] = useTransition();

  useEffect(() => {
    getUserProfile().then(p => setProfile(p));
  }, []);

  async function handleProfile(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    startTransition(async () => {
      const result = await updateProfile(formData);
      setMsg(result?.error ? { type: 'error', text: result.error } : { type: 'success', text: 'Profile updated.' });
    });
  }

  async function handlePassword(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    startTransition(async () => {
      const result = await changePassword(formData);
      if (result?.error) setMsg({ type: 'error', text: result.error });
      else { setMsg({ type: 'success', text: 'Password updated.' }); (e.target as HTMLFormElement).reset(); }
    });
  }

  const tabs = [
    { key: 'profile', label: 'Profile', icon: <User size={16} /> },
    { key: 'security', label: 'Security', icon: <Lock size={16} /> },
    { key: 'notifications', label: 'Preferences', icon: <Bell size={16} /> },
  ];

  return (
    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="max-w-2xl space-y-8">
      <div>
        <h1 className="text-3xl font-extrabold tracking-tight">Account <span className="text-violet-500">Settings</span></h1>
        <p className="text-slate-500 text-sm mt-1">Manage your identity and security configuration.</p>
      </div>

      <div className="flex gap-2 glass-panel p-1.5 rounded-2xl border-white/5 w-fit flex-wrap">
        {tabs.map(tab => (
          <button key={tab.key} onClick={() => { setActiveTab(tab.key); setMsg(null); }}
            className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all ${activeTab === tab.key ? 'bg-violet-600 text-white' : 'text-slate-400 hover:text-white'}`}>
            {tab.icon} {tab.label}
          </button>
        ))}
      </div>

      {msg && (
        <div className={`p-4 rounded-2xl flex items-center gap-2 text-sm font-bold ${msg.type === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}`}>
          {msg.type === 'success' ? <Check size={16} /> : <Info size={16} />} {msg.text}
        </div>
      )}

      <div className="glass-panel rounded-3xl border-white/5 p-8">
        {activeTab === 'profile' && (
          <form onSubmit={handleProfile} className="space-y-5">
            <h2 className="font-bold text-lg mb-6 flex items-center gap-2"><User size={18} className="text-violet-500" /> Profile Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Full Name</label>
                <input defaultValue={profile?.fullname || ''} readOnly className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm opacity-60 cursor-not-allowed" />
              </div>
              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Email</label>
                <input defaultValue={profile?.email || ''} readOnly className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm opacity-60 cursor-not-allowed" />
              </div>
            </div>
            <div className="space-y-1.5">
              <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Phone Number</label>
              <input name="phone" placeholder="+251 9XX XXX XXXX" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50 transition-all" />
            </div>
            <div className="space-y-1.5">
              <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Address</label>
              <input name="address" placeholder="Your location" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50 transition-all" />
            </div>
            <button type="submit" disabled={isPending} className="px-8 py-3 bg-violet-600 hover:bg-violet-500 rounded-xl font-bold text-sm flex items-center gap-2 disabled:opacity-50">
              {isPending ? <Loader2 size={14} className="animate-spin" /> : <Check size={14} />} Save Changes
            </button>
          </form>
        )}

        {activeTab === 'security' && (
          <form onSubmit={handlePassword} className="space-y-5">
            <h2 className="font-bold text-lg mb-6 flex items-center gap-2"><Lock size={18} className="text-violet-500" /> Change Password</h2>
            <div className="space-y-1.5">
              <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Current Password</label>
              <input name="current_password" type="password" required placeholder="••••••••" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50" />
            </div>
            <div className="space-y-1.5">
              <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">New Password</label>
              <input name="new_password" type="password" required minLength={8} placeholder="Min 8 characters" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50" />
            </div>
            <div className="space-y-1.5">
              <label className="text-xs font-bold text-slate-400 uppercase tracking-wider">Confirm New Password</label>
              <input name="confirm_password" type="password" required placeholder="••••••••" className="w-full bg-white/5 border border-white/5 rounded-xl py-3 px-4 text-sm outline-none focus:border-violet-500/50" />
            </div>
            <button type="submit" disabled={isPending} className="px-8 py-3 bg-violet-600 hover:bg-violet-500 rounded-xl font-bold text-sm flex items-center gap-2 disabled:opacity-50">
              {isPending ? <Loader2 size={14} className="animate-spin" /> : <Shield size={14} />} Update Password
            </button>
          </form>
        )}

        {activeTab === 'notifications' && (
          <div className="space-y-6">
            <h2 className="font-bold text-lg mb-6 flex items-center gap-2"><Bell size={18} className="text-violet-500" /> Notification Preferences</h2>
            {[
              { label: 'Email notifications for transactions', defaultChecked: true },
              { label: 'SMS alerts for transactions', defaultChecked: true },
              { label: 'Promotional offers and rewards', defaultChecked: false },
              { label: 'Security alerts', defaultChecked: true },
            ].map((item, i) => (
              <label key={i} className="flex items-center justify-between p-4 bg-white/3 rounded-2xl cursor-pointer hover:bg-white/5 transition-all">
                <span className="text-sm font-medium text-slate-300">{item.label}</span>
                <input type="checkbox" defaultChecked={item.defaultChecked} className="w-4 h-4 accent-violet-600" />
              </label>
            ))}
            <button className="px-8 py-3 bg-violet-600 hover:bg-violet-500 rounded-xl font-bold text-sm">Save Preferences</button>
          </div>
        )}
      </div>
    </motion.div>
  );
}
