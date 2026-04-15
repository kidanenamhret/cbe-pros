'use client';

import React, { useState, useEffect } from 'react';
import { 
  AreaChart, 
  Area, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer 
} from 'recharts';
import { motion } from 'framer-motion';

// Mock data generator for the live feel
const generateData = () => {
  const data = [];
  let baseValue = 20000;
  for (let i = 0; i < 20; i++) {
    baseValue += (Math.random() - 0.4) * 1500; // Random fluctuation trending slightly up
    data.push({
      time: `T-${20 - i}`,
      value: Math.round(baseValue),
    });
  }
  return data;
};

export default function VaultChart() {
  const [data, setData] = useState<any[]>([]);

  useEffect(() => {
    // Initial data
    setData(generateData());

    // Simulate live ticking every 5 seconds
    const interval = setInterval(() => {
      setData((prevData) => {
        const newData = [...prevData.slice(1)];
        const lastValue = prevData[prevData.length - 1].value;
        const newValue = lastValue + (Math.random() - 0.45) * 1000;
        
        newData.push({
          time: 'Now',
          value: Math.round(newValue),
        });
        
        // Update time labels
        return newData.map((item, index) => ({
          ...item,
          time: index === 19 ? 'Now' : `T-${19 - index}`
        }));
      });
    }, 5000);

    return () => clearInterval(interval);
  }, []);

  return (
    <motion.div 
      initial={{ opacity: 0, scale: 0.95 }}
      animate={{ opacity: 1, scale: 1 }}
      className="glass-panel p-6 rounded-3xl border-white/5 shadow-xl w-full h-[350px] relative"
    >
      <div className="flex justify-between items-center mb-6">
        <div>
          <h3 className="text-sm font-bold text-slate-300">Net Asset Value (Live)</h3>
          <p className="text-xs text-slate-500">Real-time aggregate vault performance</p>
        </div>
        <div className="flex gap-2 items-center">
          <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span className="text-[10px] font-bold text-emerald-500 tracking-widest uppercase">Live Sync</span>
        </div>
      </div>
      
      <div className="absolute inset-0 pt-20 pb-6 px-6">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={data}>
            <defs>
              <linearGradient id="colorValue" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#8b5cf6" stopOpacity={0.5}/>
                <stop offset="95%" stopColor="#8b5cf6" stopOpacity={0}/>
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" vertical={false} />
            <XAxis 
              dataKey="time" 
              stroke="rgba(255,255,255,0.2)" 
              fontSize={10} 
              tickMargin={10}
            />
            <YAxis 
              stroke="rgba(255,255,255,0.2)" 
              fontSize={10} 
              tickFormatter={(value) => `$${value / 1000}k`}
              width={50}
            />
            <Tooltip 
              contentStyle={{ 
                backgroundColor: 'rgba(15, 23, 42, 0.9)', 
                border: '1px solid rgba(255,255,255,0.1)',
                borderRadius: '12px',
                color: '#fff',
                fontSize: '12px'
              }}
              itemStyle={{ color: '#8b5cf6', fontWeight: 'bold' }}
            />
            <Area 
              type="monotone" 
              dataKey="value" 
              stroke="#8b5cf6" 
              strokeWidth={3}
              fillOpacity={1} 
              fill="url(#colorValue)" 
              animationDuration={500}
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </motion.div>
  );
}
