"use client";

import { useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { usePathname, useRouter } from "next/navigation";
import { cn } from "@/lib/utils";
import api from "@/lib/api";

import { WalletIndicator } from "./Wallet/WalletIndicator";
import { WalletActionModal } from "./Wallet/WalletActionModal";
import { ActiveCampaignsMenu } from "./Marketing/ActiveCampaignsMenu";
import { NotificationDropdown } from "./System/NotificationDropdown";
import { UserProfileMenu } from "./Identity/UserProfileMenu";
import { ThemeToggle } from "@/components/shared/ThemeToggle";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { Menu } from "lucide-react";

interface UserTopNavProps {
    user: any;
}

export function UserTopNav({ user }: UserTopNavProps) {
    const pathname = usePathname();
    const router = useRouter();
    
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const [walletModalOpen, setWalletModalOpen] = useState(false);
    const [walletMode, setWalletMode] = useState<"add" | "withdraw">("add");

    const handleLogout = async () => {
        try { await api.post("/logout"); } catch(e) {}
        localStorage.removeItem("auth_token");
        router.push("/login");
    };

    const navLinks = [
        { href: "/portfolio", label: "Portfolio" },
        { href: "/transactions", label: "Transactions" },
        { href: "/plan", label: "Plans" },
    ];

    const isActive = (path: string) => pathname?.startsWith(path);

    return (
        <>
            <header className="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="container flex h-16 items-center px-4">
                    {/* 1. Brand / Logo (Desktop) */}
                    <div className="mr-4 hidden md:flex">
                        <Link href="/dashboard" className="mr-6 flex items-center space-x-2">
                             <div className="relative h-8 w-[120px]">
                                <Image src="/preiposip.png" alt="PreIPO SIP" fill className="object-contain" priority />
                             </div>
                        </Link>
                        <nav className="flex items-center space-x-6 text-sm font-medium">
                            {navLinks.map((link) => (
                                <Link key={link.href} href={link.href} className={cn("transition-colors hover:text-foreground/80", isActive(link.href) ? "text-foreground" : "text-foreground/60")}>
                                    {link.label}
                                </Link>
                            ))}
                        </nav>
                    </div>

                    {/* 2. Mobile Menu Trigger */}
                    <div className="flex md:hidden">
                        <Sheet open={isMobileMenuOpen} onOpenChange={setIsMobileMenuOpen}>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon" className="-ml-2"><Menu className="h-5 w-5" /><span className="sr-only">Toggle Menu</span></Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="pr-0">
                                <div className="px-7">
                                    <Link href="/dashboard" className="flex items-center" onClick={() => setIsMobileMenuOpen(false)}>
                                        <span className="font-bold">PreIPO SIP</span>
                                    </Link>
                                    <div className="mt-8 flex flex-col gap-4">
                                        {navLinks.map((link) => (
                                            <Link 
                                                key={link.href} 
                                                href={link.href}
                                                onClick={() => setIsMobileMenuOpen(false)}
                                                className={cn("text-lg font-medium", isActive(link.href) ? "text-primary" : "text-muted-foreground")}
                                            >
                                                {link.label}
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    {/* 3. Actions (Right Side) */}
                    <div className="flex flex-1 items-center justify-end space-x-2">
                        <ActiveCampaignsMenu />
                        <WalletIndicator 
                            onAddFunds={() => { setWalletMode("add"); setWalletModalOpen(true); }} 
                            onWithdraw={() => { setWalletMode("withdraw"); setWalletModalOpen(true); }} 
                        />
                        <NotificationDropdown />
                        <ThemeToggle />
                        <UserProfileMenu user={user} onLogout={handleLogout} />
                    </div>
                </div>
            </header>
            
            <WalletActionModal isOpen={walletModalOpen} onClose={() => setWalletModalOpen(false)} mode={walletMode} />
        </>
    );
}