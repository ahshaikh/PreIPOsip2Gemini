// app/layout.tsx
import "./globals.css";
import Navbar from "@/components/shared/Navbar";
import Footer from "@/components/shared/Footer";
import ScrollToTop from "@/components/shared/ScrollToTop";
import ReactQueryProvider from "@/providers/ReactQueryProvider";
import { AuthProvider } from "@/context/AuthContext";

export const metadata = {
  title: "PreIPOsip",
  description: "Invest early. Invest smart.",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>
        <ReactQueryProvider>
          <AuthProvider>
            <Navbar />
            {children}
            <Footer />
            <ScrollToTop />
          </AuthProvider>
        </ReactQueryProvider>
      </body>
    </html>
  );
}
