# Secrets Rotation Guide
## PreIPO SIP Platform - Security Operations Manual

**Last Updated:** November 26, 2025
**Document Version:** 1.0
**Classification:** Internal - Confidential

---

## 📋 Table of Contents

1. [Overview]			(#overview)
2. [Secrets Inventory]		(#secrets-inventory)
3. [Rotation Schedule]		(#rotation-schedule)
4. [Rotation Procedures]	(#rotation-procedures)
5. [Emergency Rotation]		(#emergency-rotation)
6. [Audit & Compliance]		(#audit--compliance)

---

## Overview

This guide outlines procedures for rotating cryptographic keys, API tokens, passwords, and other sensitive credentials used in the PreIPO SIP platform. Regular rotation reduces the risk of credential compromise.

### Why Rotate Secrets?

- **Limit exposure window**: Compromised credentials have limited lifespan
- **Compliance**: Meet regulatory requirements (SOC 2, PCI DSS, ISO 27001)
- **Best practice**: Industry standard security measure
- **Defense in depth**: Additional security layer

---

## Secrets Inventory

### 1. Application Secrets (Laravel Backend)

| Secret 		| Location 	| Rotation Frequency 	| Priority |
|-----------------------|---------------|-----------------------|----------|
| `APP_KEY` 		| `.env` 	| Annually 		| HIGH 	   |
| `DB_PASSWORD` 	| `.env` 	| Quarterly 		| CRITICAL |
| `JWT_SECRET` 		| `.env` 	| Quarterly 		| HIGH     |
| `SESSION_SECRET` 	| `.env` 	| Quarterly 		| HIGH     |

### 2. Third-Party API Keys

| Service 		| Secret Keys 							| Rotation Frequency 	| Priority 	|
|-----------------------|---------------------------------------------------------------|-----------------------|---------------|
| **Razorpay** 		| `RAZORPAY_KEY`, `RAZORPAY_SECRET`, `RAZORPAY_WEBHOOK_SECRET` 	| Annually 		| CRITICAL 	|
| **SMS Gateway** 	| `SMS_API_KEY`, `SMS_API_SECRET` 				| Annually 		| HIGH 		|
| **Email Provider** 	| `MAIL_PASSWORD`, `SENDGRID_API_KEY` 				| Annually 		| MEDIUM 	|
| **DigiLocker** 	| `DIGILOCKER_CLIENT_ID`, `DIGILOCKER_CLIENT_SECRET` 		| Annually 		| HIGH 		|
| **Sentry** 		| `SENTRY_DSN`, `SENTRY_AUTH_TOKEN` 				| Annually 		| LOW 		|

### 3. Storage & Infrastructure

| Secret 			| Location 		| Rotation Frequency 	| Priority 	|
|-------------------------------|-----------------------|-----------------------|---------------|
| SSH Keys 			| Server 		| Annually 		| HIGH 		|
| SSL/TLS Certificates 		| Web Server 		| 90 days (auto) 	| CRITICAL 	|
| Database Admin Password 	| Database Server 	| Quarterly 		| CRITICAL 	|
| Redis Password 		| Redis Server 		| Quarterly 		| MEDIUM 	|

### 4. Frontend Secrets

| Secret 			| Location 		| Rotation Frequency 	| Priority |
|-------------------------------|-----------------------|-----------------------|----------|
| `NEXT_PUBLIC_ENCRYPTION_KEY` 	| Frontend `.env` 	| Annually 		| MEDIUM   |
| Sentry DSN 			| Frontend `.env` 	| As needed 		| LOW 	   |

## Rotation Schedule

### Quarterly (Every 3 Months)
- [ ] Database passwords (all environments)
- [ ] JWT secrets
- [ ] Session secrets
- [ ] Redis passwords

### Semi-Annually (Every 6 Months)
- [ ] Review all API keys for unused/deprecated services
- [ ] Rotate admin user passwords
- [ ] Update SSH keys on production servers

### Annually (Every 12 Months)
- [ ] Laravel `APP_KEY`
- [ ] Razorpay API keys
- [ ] SMS gateway credentials
- [ ] Email service credentials
- [ ] DigiLocker OAuth credentials
- [ ] Frontend encryption key

### As Needed
- [ ] After security incident
- [ ] When employee with access leaves
- [ ] When credential appears in public repository
- [ ] When service provider recommends rotation

## Rotation Procedures

### 1. Laravel APP_KEY Rotation

**When to Rotate:** Annually or after suspected compromise

**Impact:**
- ⚠️ All encrypted data (cookies, session data) will be invalidated
- Users will be logged out
- Encrypted database fields may need re-encryption

**Procedure:**

```bash
# Step 1: Backup current key
echo "Current APP_KEY: $(grep APP_KEY .env | cut -d '=' -f2)" >> app_key_history.txt

# Step 2: Generate new key
php artisan key:generate --show

# Step 3: Update .env file manually (to maintain control)
# OLD: APP_KEY=base64:OLD_KEY_HERE
# NEW: APP_KEY=base64:NEW_KEY_HERE

# Step 4: Update deployment secrets (GitHub Actions, etc.)
# Update in: GitHub Secrets, CI/CD pipeline

# Step 5: Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Step 6: Restart services
sudo systemctl restart php-fpm
sudo systemctl restart nginx

# Step 7: Announce to users (optional)
# "We've upgraded our security. Please log in again."
```

**Rollback Plan:**
```bash
# If issues occur, restore old key
# Update .env with backed up key
php artisan config:clear
sudo systemctl restart php-fpm
```

### 2. Database Password Rotation

**When to Rotate:** Quarterly

**Impact:**
- ⚠️ Application downtime during rotation (2-5 minutes)
- All application instances must be updated

**Procedure:**

```bash
# Step 1: Generate strong password
NEW_PASSWORD=$(openssl rand -base64 32)
echo "New password: $NEW_PASSWORD"

# Step 2: Update database user password
mysql -u root -p <<EOF
ALTER USER 'app_user'@'localhost' IDENTIFIED BY '$NEW_PASSWORD';
FLUSH PRIVILEGES;
EOF

# Step 3: Update .env file
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$NEW_PASSWORD/" .env

# Step 4: Test connection
php artisan migrate:status

# Step 5: Update all application servers (if load balanced)
# Use Ansible/Chef/Puppet or manual SSH

# Step 6: Restart services
php artisan config:clear
sudo systemctl restart php-fpm

# Step 7: Verify application works
curl https://yourdomain.com/health
```

**Rollback Plan:**
Keep old password active for 24 hours, update back if needed.

### 3. Razorpay API Keys Rotation

**When to Rotate:** Annually

**Impact:**
- Payment processing will fail with old keys
- Zero downtime if rotated correctly

**Procedure:**

```bash
# Step 1: Generate new keys in Razorpay Dashboard
# 1. Log in to Razorpay Dashboard
# 2. Settings → API Keys → Regenerate Key
# 3. Save new Key ID and Secret

# Step 2: Test new keys in staging environment
# Update staging .env:
RAZORPAY_KEY=rzp_test_NEW_KEY
RAZORPAY_SECRET=NEW_SECRET

# Test payment flow in staging
php artisan test --filter PaymentTest

# Step 3: Schedule maintenance window (optional, for safety)
# Or deploy during low traffic (2-4 AM)

# Step 4: Update production .env
RAZORPAY_KEY=rzp_live_NEW_KEY
RAZORPAY_SECRET=NEW_SECRET
RAZORPAY_WEBHOOK_SECRET=NEW_WEBHOOK_SECRET

# Step 5: Update webhook URL in Razorpay Dashboard
# Settings → Webhooks → Edit → Update Secret

# Step 6: Deploy changes
php artisan config:clear
sudo systemctl restart php-fpm

# Step 7: Monitor for 24 hours
# Check payment success rate
# Monitor error logs: tail -f storage/logs/laravel.log
```

**Verification:**
```bash
# Test payment creation
php artisan tinker
>>> $order = \App\Services\RazorpayService::createOrder(1000);
>>> $order->id; // Should return order ID
```

### 4. JWT Secret Rotation

**When to Rotate:** Quarterly

**Impact:**
- ⚠️ All active JWT tokens will be invalidated
- Users will need to log in again

**Procedure:**

```bash
# Step 1: Announce maintenance to users
# "Scheduled security maintenance at 2 AM"

# Step 2: Generate new secret
NEW_JWT_SECRET=$(openssl rand -hex 64)

# Step 3: Update .env
JWT_SECRET=$NEW_JWT_SECRET

# Step 4: Clear token caches
php artisan cache:forget jwt:*

# Step 5: Restart
php artisan config:clear
sudo systemctl restart php-fpm

# Step 6: Notify users
# In-app notification: "Please log in again for security reasons"
```

### 5. Frontend Encryption Key Rotation

**When to Rotate:** Annually

**Impact:**
- Existing encrypted tokens in localStorage will fail to decrypt
- Users will be auto-logged out (migration handles this)

**Procedure:**

```bash
# Step 1: Generate new key
NEW_ENCRYPTION_KEY=$(openssl rand -hex 32)

# Step 2: Update frontend .env
NEXT_PUBLIC_ENCRYPTION_KEY=$NEW_ENCRYPTION_KEY

# Step 3: Rebuild frontend
cd frontend
npm run build

# Step 4: Deploy
# Deploy to Vercel/Netlify or your hosting

# Step 5: Users will be prompted to log in again
# The secureStorage utility handles migration gracefully
```

## Emergency Rotation

### When to Perform Emergency Rotation

- ✅ Credentials leaked in public repository (GitHub, GitLab)
- ✅ Credentials found in application logs
- ✅ Suspected unauthorized access
- ✅ Employee with credential access terminated
- ✅ Third-party breach affecting your credentials

### Emergency Procedure

1. **Immediate Actions (Within 1 Hour)**
   ```bash
   # 1. Identify compromised credential
   # 2. Disable/revoke immediately if possible
   # 3. Generate replacement
   # 4. Update production
   # 5. Monitor for suspicious activity
   ```

2. **Communication**
   - Notify security team immediately
   - Document incident
   - Update stakeholders
   - Prepare post-mortem

3. **Verification**
   - Review access logs for unauthorized use
   - Check for data exfiltration
   - Verify rotation success

## Audit & Compliance

### Rotation Tracking

Maintain a log of all rotations:
Date: 2025-11-26
Secret: DB_PASSWORD
Rotated By: admin@company.com
Reason: Quarterly rotation
Success: Yes
Issues: None
```

### Checklist Template

```markdown
## Secrets Rotation - [QUARTER] [YEAR]

### Pre-Rotation
- [ ] Backup current secrets
- [ ] Test rotation procedure in staging
- [ ] Notify team of maintenance window
- [ ] Prepare rollback plan

### Rotation
- [ ] Rotate DB_PASSWORD
- [ ] Rotate JWT_SECRET
- [ ] Rotate SESSION_SECRET
- [ ] Rotate Redis password
- [ ] Update all application servers
- [ ] Verify applications work

### Post-Rotation
- [ ] Monitor logs for 24 hours
- [ ] Update password manager
- [ ] Document completion
- [ ] Schedule next rotation
```

### Compliance Requirements

| Standard 	| Requirement 			| Our Practice 		|
|---------------|-------------------------------|-----------------------|
| **PCI DSS** 	| Rotate keys annually 		| ✅ Annually 		|
| **SOC 2** 	| Document rotation policy 	| ✅ This document 	|
| **ISO 27001** | Regular key management review | ✅ Quarterly review 	|
| **GDPR**	| Encrypt sensitive data 	| ✅ Multiple layers 	|

## Tools & Automation

### Recommended Tools

1. **Password Managers**
   - 1Password Teams
   - LastPass Enterprise
   - HashiCorp Vault (advanced)

2. **Secrets Management**
   - AWS Secrets Manager
   - Azure Key Vault
   - Google Cloud Secret Manager

3. **Monitoring**
   - GitGuardian (scan for leaked secrets)
   - TruffleHog (scan repositories)
   - Sentry (error tracking)

### Automation Scripts

```bash
# Helper script for quarterly rotation
#!/bin/bash
# quarterly-rotation.sh

echo "Starting quarterly secrets rotation..."

# Generate new secrets
NEW_DB_PASS=$(openssl rand -base64 32)
NEW_JWT_SECRET=$(openssl rand -hex 64)
NEW_SESSION_SECRET=$(openssl rand -hex 32)

echo "Generated new secrets:"
echo "DB_PASSWORD: $NEW_DB_PASS"
echo "JWT_SECRET: $NEW_JWT_SECRET"
echo "SESSION_SECRET: $NEW_SESSION_SECRET"

# Backup current .env
cp .env .env.backup.$(date +%Y%m%d)

echo "Backup created. Proceed with manual updates."
echo "Follow the procedures in SECRETS-ROTATION-GUIDE.md"
```

## Contact & Support

**Security Team:**
- Email: security@company.com
- Slack: #security-team
- On-Call: [PagerDuty link]

**Escalation:**
- Level 1: Team Lead
- Level 2: CTO
- Level 3: CEO

## Appendix

### A. Secret Strength Requirements

- **Passwords:** Min 16 characters, mixed case, numbers, symbols
- **API Keys:** Provider-generated (don't create manually)
- **JWT Secrets:** Min 256 bits (64 hex characters)
- **Encryption Keys:** Min 256 bits (32 bytes)

### B. Storage Best Practices

- ✅ Store in encrypted password manager
- ✅ Use environment variables in production
- ✅ Never commit to version control
- ✅ Limit access to need-to-know basis
- ✅ Enable MFA on secret storage systems
- ❌ Never share via email or chat
- ❌ Never hardcode in application code
- ❌ Never log secrets

### C. Incident Response Checklist

```markdown
## Security Incident - Secret Compromise

**Date:** __________
**Discovered By:** __________
**Compromised Secret:** __________

### Immediate Actions (0-1 hour)
- [ ] Revoke/disable compromised credential
- [ ] Generate replacement
- [ ] Deploy replacement
- [ ] Monitor for unauthorized access

### Investigation (1-24 hours)
- [ ] Review access logs
- [ ] Identify scope of compromise
- [ ] Document timeline
- [ ] Notify affected parties

### Remediation (24-72 hours)
- [ ] Rotate related credentials
- [ ] Update security procedures
- [ ] Conduct post-mortem
- [ ] Implement preventive measures

### Follow-up
- [ ] File incident report
- [ ] Update documentation
- [ ] Train team on lessons learned
```


**Document Maintainer:** Security Team
**Next Review Date:** February 26, 2026
**Version History:**
- v1.0 (2025-11-26): Initial release
