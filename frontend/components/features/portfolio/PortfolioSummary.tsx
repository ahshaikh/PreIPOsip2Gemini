'use client';

/**
 * PortfolioSummary
 * * [AUDIT FIX]: Consumes backend-driven valuation.
 * * No local math performed to ensure data integrity.
 */
export function PortfolioSummary({ summary }) {
  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div className="p-4 border rounded shadow-sm">
        <label className="text-sm text-muted-foreground">Total Invested</label>
        <p className="text-2xl font-bold">₹{summary.total_invested}</p>
      </div>
      
      <div className="p-4 border rounded shadow-sm">
        <label className="text-sm text-muted-foreground">Net Gain/Loss</label>
        <p className={`text-2xl font-bold ${summary.net_gain_loss >= 0 ? 'text-green-600' : 'text-red-600'}`}>
          ₹{summary.net_gain_loss} ({summary.percentage_gain})
        </p>
      </div>

      {/* [AUDIT FIX]: Displaying backend-calculated sector weightage */}
      <div className="p-4 border rounded shadow-sm">
        <label className="text-sm text-muted-foreground">Top Sector</label>
        <p className="text-lg font-medium">
          {Object.keys(summary.sector_weightage)[0] || 'N/A'}
        </p>
      </div>
    </div>
  );
}