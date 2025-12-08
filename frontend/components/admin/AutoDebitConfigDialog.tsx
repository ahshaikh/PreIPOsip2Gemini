// V-PHASE6-AUTODEBIT-1208 (Created)
'use client';

import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Calendar, Info, RefreshCw } from 'lucide-react';

interface AutoDebitConfig {
  enabled?: boolean;
  payment_day?: number;
  retry_attempts?: number;
  retry_interval_days?: number;
  auto_pause_after_failures?: number;
  notification_days_before?: number;
}

interface AutoDebitConfigDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planName: string;
  autoDebitConfig: AutoDebitConfig;
  onSave: (config: AutoDebitConfig) => void;
  isSaving: boolean;
}

export function AutoDebitConfigDialog({
  open,
  onOpenChange,
  planName,
  autoDebitConfig,
  onSave,
  isSaving
}: AutoDebitConfigDialogProps) {
  const [enabled, setEnabled] = useState(true);
  const [paymentDay, setPaymentDay] = useState('5');
  const [retryAttempts, setRetryAttempts] = useState('3');
  const [retryIntervalDays, setRetryIntervalDays] = useState('3');
  const [autoPauseAfterFailures, setAutoPauseAfterFailures] = useState('3');
  const [notificationDaysBefore, setNotificationDaysBefore] = useState('3');

  useEffect(() => {
    if (open) {
      setEnabled(autoDebitConfig.enabled ?? true);
      setPaymentDay(autoDebitConfig.payment_day?.toString() || '5');
      setRetryAttempts(autoDebitConfig.retry_attempts?.toString() || '3');
      setRetryIntervalDays(autoDebitConfig.retry_interval_days?.toString() || '3');
      setAutoPauseAfterFailures(autoDebitConfig.auto_pause_after_failures?.toString() || '3');
      setNotificationDaysBefore(autoDebitConfig.notification_days_before?.toString() || '3');
    }
  }, [open, autoDebitConfig]);

  const handleSave = () => {
    const config: AutoDebitConfig = {
      enabled,
      payment_day: parseInt(paymentDay),
      retry_attempts: parseInt(retryAttempts),
      retry_interval_days: parseInt(retryIntervalDays),
      auto_pause_after_failures: parseInt(autoPauseAfterFailures),
      notification_days_before: parseInt(notificationDaysBefore)
    };
    onSave(config);
  };

  const paymentDayNum = parseInt(paymentDay);
  const isValidPaymentDay = paymentDayNum >= 1 && paymentDayNum <= 28;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Calendar className="h-5 w-5" />
            Auto-Debit Configuration - {planName}
          </DialogTitle>
          <DialogDescription>
            Configure automatic payment scheduling and retry logic
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Enable/Disable */}
          <div className="flex items-center justify-between p-4 border rounded-lg bg-muted/30">
            <div className="space-y-1">
              <Label className="text-base font-semibold">Enable Auto-Debit</Label>
              <p className="text-sm text-muted-foreground">
                Automatically charge subscribers on scheduled dates
              </p>
            </div>
            <Switch checked={enabled} onCheckedChange={setEnabled} />
          </div>

          {enabled && (
            <>
              <div className="flex items-start gap-3 p-4 bg-muted/50 rounded-lg">
                <Info className="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div className="text-sm text-muted-foreground">
                  <p className="font-medium mb-1">How Auto-Debit Works:</p>
                  <p>Payments are automatically charged on the specified day each month. If a payment fails, the system will retry based on your configuration.</p>
                </div>
              </div>

              {/* Payment Day */}
              <div className="space-y-2">
                <Label>Payment Day of Month</Label>
                <Select value={paymentDay} onValueChange={setPaymentDay}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent className="max-h-[200px]">
                    {Array.from({ length: 28 }, (_, i) => i + 1).map(day => (
                      <SelectItem key={day} value={day.toString()}>
                        {day}{day === 1 ? 'st' : day === 2 ? 'nd' : day === 3 ? 'rd' : 'th'} of each month
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <p className="text-xs text-muted-foreground">
                  Day of the month when payment will be auto-debited (1-28 to avoid month-end issues)
                </p>
              </div>

              {/* Retry Configuration */}
              <div className="space-y-4 p-4 border rounded-lg">
                <div className="flex items-center gap-2">
                  <RefreshCw className="h-4 w-4" />
                  <h4 className="font-semibold">Retry Configuration</h4>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Maximum Retry Attempts</Label>
                    <Input
                      type="number"
                      min="0"
                      max="10"
                      value={retryAttempts}
                      onChange={(e) => setRetryAttempts(e.target.value)}
                    />
                    <p className="text-xs text-muted-foreground">
                      Number of times to retry failed payments
                    </p>
                  </div>

                  <div className="space-y-2">
                    <Label>Retry Interval (Days)</Label>
                    <Input
                      type="number"
                      min="1"
                      max="30"
                      value={retryIntervalDays}
                      onChange={(e) => setRetryIntervalDays(e.target.value)}
                    />
                    <p className="text-xs text-muted-foreground">
                      Days to wait between retry attempts
                    </p>
                  </div>
                </div>
              </div>

              {/* Auto-Pause Configuration */}
              <div className="space-y-2">
                <Label>Auto-Pause After Failed Attempts</Label>
                <Input
                  type="number"
                  min="0"
                  max="10"
                  value={autoPauseAfterFailures}
                  onChange={(e) => setAutoPauseAfterFailures(e.target.value)}
                />
                <p className="text-xs text-muted-foreground">
                  Automatically pause subscription after this many consecutive failures (0 = never pause)
                </p>
              </div>

              {/* Notification Configuration */}
              <div className="space-y-2">
                <Label>Payment Reminder (Days Before)</Label>
                <Input
                  type="number"
                  min="0"
                  max="7"
                  value={notificationDaysBefore}
                  onChange={(e) => setNotificationDaysBefore(e.target.value)}
                />
                <p className="text-xs text-muted-foreground">
                  Send payment reminder notification this many days before debit date
                </p>
              </div>

              {/* Example Timeline */}
              <Alert>
                <Calendar className="h-4 w-4" />
                <AlertDescription>
                  <strong>Example Timeline:</strong>
                  <div className="mt-2 space-y-1 text-sm">
                    <p>• Day {Math.max(1, paymentDayNum - parseInt(notificationDaysBefore))}: Payment reminder sent</p>
                    <p>• Day {paymentDayNum}: First auto-debit attempt</p>
                    <p>• Day {paymentDayNum + parseInt(retryIntervalDays)}: 1st retry (if failed)</p>
                    <p>• Day {paymentDayNum + (2 * parseInt(retryIntervalDays))}: 2nd retry (if failed)</p>
                    {parseInt(autoPauseAfterFailures) > 0 && (
                      <p className="text-muted-foreground">
                        • Subscription auto-paused after {autoPauseAfterFailures} consecutive failures
                      </p>
                    )}
                  </div>
                </AlertDescription>
              </Alert>
            </>
          )}
        </div>

        <div className="flex gap-2 pt-4">
          <Button variant="outline" onClick={() => onOpenChange(false)} className="flex-1" disabled={isSaving}>
            Cancel
          </Button>
          <Button
            onClick={handleSave}
            className="flex-1"
            disabled={isSaving || (enabled && !isValidPaymentDay)}
          >
            {isSaving ? 'Saving...' : 'Save Auto-Debit Config'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
