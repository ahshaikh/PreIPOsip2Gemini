import { Request, Response } from 'express';
import mysql from 'mysql2/promise';

// Database connection (Ideally imported from your db config file)
const pool = mysql.createPool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
});

export const getDashboardOverview = async (req: Request, res: Response) => {
  try {
    // 1. Identify User (Middleware should have already populated req.user.id)
    // const userId = req.user.id; 
    const userId = 1; // HARDCODED for testing. Replace with actual auth logic.

    // 2. Parallel Execution: Run all queries at once for speed
    const [
      [userRows],
      [portfolioRows],
      [kycRows],
      [subRows],
      [bonusRows],
      [referralRows],
      [walletRows],
      [activityRows],
      [notifRows]
    ] = await Promise.all([
      pool.query('SELECT first_name FROM users WHERE id = ?', [userId]),
      pool.query('SELECT current_value, total_invested FROM portfolios WHERE user_id = ?', [userId]),
      pool.query('SELECT status FROM kyc WHERE user_id = ?', [userId]),
      // Join logic to get Plan Name
      pool.query(`
        SELECT s.status, s.next_payment_date, p.name 
        FROM subscriptions s 
        JOIN plans p ON s.plan_id = p.id 
        WHERE s.user_id = ? AND s.status = 'active'
        LIMIT 1`, [userId]),
      pool.query('SELECT amount FROM bonuses WHERE user_id = ?', [userId]),
      pool.query('SELECT total_referrals, active_referrals, total_earnings FROM referrals WHERE user_id = ?', [userId]),
      pool.query('SELECT balance FROM wallets WHERE user_id = ?', [userId]),
      pool.query('SELECT description, amount, type, created_at FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5', [userId]),
      pool.query('SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0', [userId]),
    ]);

    // 3. Extract Data (Safety Checks)
    const user = (userRows as any[])[0] || {};
    const portfolio = (portfolioRows as any[])[0] || { current_value: 0, total_invested: 0 };
    const kyc = (kycRows as any[])[0] || { status: 'pending' };
    const subscription = (subRows as any[])[0] || {};
    const wallet = (walletRows as any[])[0] || { balance: 0 };
    const referral = (referralRows as any[])[0] || { total_referrals: 0, active_referrals: 0, total_earnings: 0 };
    const notifCount = (notifRows as any[])[0]?.count || 0;

    // Calculate Total Bonuses (Summing up the array)
    const totalBonuses = (bonusRows as any[]).reduce((acc, row) => acc + (parseFloat(row.amount) || 0), 0);

    // 4. Perform Financial Math (Server-Side Source of Truth)
    const currentVal = parseFloat(portfolio.current_value) || 0;
    const investedVal = parseFloat(portfolio.total_invested) || 0;
    
    let portfolioChangePercent = 0;
    let isPositive = true;

    if (investedVal > 0) {
      const rawChange = ((currentVal - investedVal) / investedVal) * 100;
      portfolioChangePercent = Number(rawChange.toFixed(2));
      isPositive = rawChange >= 0;
    }

    // 5. Send Clean JSON
    res.json({
      user: { firstName: user.first_name },
      stats: {
        portfolioValue: currentVal,
        totalInvested: investedVal,
        portfolioChangePercent,
        isPositive,
        unrealizedGain: (currentVal - investedVal).toFixed(2),
        walletBalance: parseFloat(wallet.balance) || 0,
        totalBonuses,
      },
      status: {
        kyc: kyc.status || 'pending',
        subscription: {
          name: subscription.name || 'No Plan',
          status: subscription.status || 'inactive',
          nextPaymentDate: subscription.next_payment_date || null,
        },
        referrals: {
          total: referral.total_referrals,
          active: referral.active_referrals,
          earnings: referral.total_earnings,
        },
        notificationCount: notifCount,
      },
      activity: activityRows || []
    });

  } catch (error) {
    console.error('Dashboard Error:', error);
    res.status(500).json({ error: 'Server Error' });
  }
};