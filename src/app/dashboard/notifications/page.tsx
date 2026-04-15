'use client';
import React, { useEffect, useState, useTransition } from 'react';
import { motion } from 'framer-motion';
import { getNotifications, markNotificationsRead } from '@/lib/actions/services';
import { Bell, Check, ArrowDownLeft, ArrowUpRight, Info, ShieldAlert, Gift } from 'lucide-react';

const typeIcon = (type: string) => {
  switch (type) {
    case 'transaction': return <ArrowDownLeft size={16} className="text-emerald-400" />;
    case 'withdrawal': return <ArrowUpRight size={16} className="text-rose-400" />;
    case 'security': return <ShieldAlert size={16} className="text-amber-400" />;
    case 'promotion': return <Gift size={16} className="text-sky-400" />;
    default: return <Info size={16} className="text-violet-400" />;
  }
};

export default function NotificationsPage() {
  const [notifications, setNotifications] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isPending, startTransition] = useTransition();

  useEffect(() => {
    getNotifications().then(d => { setNotifications(d); setLoading(false); });
  }, []);

  function handleMarkRead() {
    startTransition(async () => {
      await markNotificationsRead();
      setNotifications(prev => prev.map(n => ({ ...n, is_read: true })));
    });
  }

  const unread = notifications.filter(n => !n.is_read).length;

  return (
    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="max-w-2xl space-y-8">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 className="text-3xl font-extrabold tracking-tight flex items-center gap-2">
            Notifications {unread > 0 && <span className="w-6 h-6 bg-rose-500 rounded-full text-xs font-bold flex items-center justify-center">{unread}</span>}
          </h1>
          <p className="text-slate-500 text-sm mt-1">Your system activity feed.</p>
        </div>
        {unread > 0 && (
          <button onClick={handleMarkRead} disabled={isPending} className="flex items-center gap-2 px-4 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-sm font-bold transition-all disabled:opacity-50">
            <Check size={14} /> Mark All Read
          </button>
        )}
      </div>

      <div className="glass-panel rounded-3xl border-white/5 overflow-hidden">
        {loading ? <div className="p-12 text-center text-slate-500 animate-pulse">Loading…</div> :
         notifications.length === 0 ? <div className="p-12 text-center text-slate-500">No notifications.</div> : (
          <div className="divide-y divide-white/5">
            {notifications.map((n: any) => (
              <div key={n.id} className={`flex items-start gap-4 p-5 transition-all ${!n.is_read ? 'bg-violet-500/5' : ''}`}>
                <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${!n.is_read ? 'bg-violet-600/10' : 'bg-white/5'}`}>
                  {typeIcon(n.type)}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <div className="font-bold text-sm">{n.title}</div>
                    {!n.is_read && <div className="w-1.5 h-1.5 bg-violet-500 rounded-full" />}
                  </div>
                  <div className="text-slate-400 text-xs mt-1">{n.message}</div>
                  <div className="text-slate-600 text-[10px] mt-1">{new Date(n.created_at).toLocaleString()}</div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </motion.div>
  );
}
