import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Lampung Geh News — Berita Terkini dan Terpercaya seputar Lampung!",
  description:
    "Berita terkini dan terpercaya seputar Lampung. Liputan jernih, cepat, dan mendalam dari 15 kabupaten/kota.",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="id-ID" className="scroll-smooth">
      <body className="bg-canvas text-body selection:bg-brand selection:text-white">
        {children}
      </body>
    </html>
  );
}
