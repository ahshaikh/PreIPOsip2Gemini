export interface WalletBalance {
    currency: 'INR';
    amount: number;
    formatted: string;
    is_locked: boolean;
    locked_amount: number;
    last_updated: string;
}

export interface WalletConfig {
    capabilities: {
        can_deposit: boolean;
        can_withdraw: boolean;
    };
    limits: {
        deposit: { min: number; max: number; step: number };
        withdrawal: { min: number; max: number; requires_manual_approval_above: number };
    };
    messages: {
        withdrawal_blocked: string | null;
        sla_text: string;
    };
}

export interface WithdrawalQuote {
    requested_amount: number;
    breakdown: { fee: number; tds: number };
    net_amount: number;
    workflow: { requires_manual_review: boolean; estimated_settlement: string };
    disclaimer: string;
}