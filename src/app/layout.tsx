import type { Metadata } from "next";
import { Plus_Jakarta_Sans } from "next/font/google";
import "./globals.css";

const plusJakarta = Plus_Jakarta_Sans({ 
  subsets: ["latin"],
  variable: '--font-plus-jakarta'
});

export const metadata: Metadata = {
  title: "Mesfin Digital Bank | The Next Generation of Finance",
  description: "Secure, boundless, and architected for the future of digital assets.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className="dark">
      <body className={`${plusJakarta.variable} font-sans antialiased selection:bg-violet-500/30`}>
        {children}
      </body>
    </html>
  );
}
