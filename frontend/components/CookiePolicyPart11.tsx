'use client';

import React from 'react';
import { ShieldCheck, Lock, Key, Server } from 'lucide-react';

export default function CookiePolicyPart11() {
  return (
    <section id="part-6" className="section mb-12">
      <div className="section-header border-b border-gray-200 pb-4 mb-8">
        <span className="section-number text-indigo-600 font-mono text-lg font-bold mr-4">PART 6</span>
        <h2 className="section-title font-serif text-3xl text-slate-900 inline-block">DATA SECURITY MEASURES, TECHNICAL SAFEGUARDS, INCIDENT RESPONSE, AND BREACH NOTIFICATION FRAMEWORK</h2>
      </div>

      {/* 6.1 */}
      <div id="point-6-1" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <ShieldCheck className="text-indigo-600" size={20} /> 6.1 STATUTORY SECURITY OBLIGATIONS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.1.1 Section 43A of Information Technology Act, 2000:</h4>
            <p className="text-slate-600 mb-2">The Platform, as a "body corporate" within the meaning of Section 43A of the IT Act, 2000, is obligated to implement and maintain "reasonable security practices and procedures" to protect Sensitive Personal Data or Information (SPDI).</p>
            <p className="text-slate-600 mb-2">Failure to maintain such reasonable security practices rendering the Platform liable to pay damages by way of compensation to affected persons, not exceeding INR 5 crores per affected person.</p>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.1.2 Definition of "Reasonable Security Practices":</h4>
            <p className="text-slate-600 mb-2">Pursuant to Rule 8 of the IT (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>IS/ISO/IEC 27001 Compliance:</strong> Implementation of comprehensive information security management system (ISMS) documented in information security policy approved by Board of Directors;</li>
                <li>(b) <strong>Alternative Standards:</strong> BS 7799, ISO/IEC 27001, or such other standards approved by Central Government;</li>
                <li>(c) <strong>Contractual Protection:</strong> Ensuring comprehensive contracts with third parties accessing SPDI, imposing equivalent security obligations;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.1.3 SEBI Technology Requirements:</h4>
            <p className="text-slate-600 mb-2">Pursuant to SEBI (Investment Advisers) Regulations, 2013 and related circulars:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) Maintenance of robust business continuity and disaster recovery plans;</li>
                <li>(b) Cybersecurity frameworks compliant with SEBI Cybersecurity and Cyber Resilience Framework (CSCRF);</li>
                <li>(c) Regular security audits by CERT-In empaneled auditors;</li>
                <li>(d) Incident reporting to SEBI within prescribed timelines;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.1.4 RBI Guidelines for Payment Data:</h4>
            <p className="text-slate-600 mb-2">Where Platform processes payment data, compliance with:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) RBI Master Direction on Digital Payment Security Controls, 2021;</li>
                <li>(b) Payment Card Industry Data Security Standard (PCI-DSS) v3.2.1 or higher;</li>
                <li>(c) Two-factor authentication (2FA) for all payment transactions;</li>
                <li>(d) Data localization requirements for payment system data;</li>
            </ul>
        </div>
      </div>

      {/* 6.2 */}
      <div id="point-6-2" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Lock className="text-indigo-600" size={20} /> 6.2 COMPREHENSIVE SECURITY ARCHITECTURE
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.2.1 Defense-in-Depth Strategy:</h4>
            <p className="text-slate-600 mb-2">The Platform implements multi-layered security controls across:</p>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Perimeter Security Layer:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Web Application Firewall (WAF) with OWASP Top 10 protection;</li>
                        <li>DDoS mitigation services (Cloudflare, AWS Shield Advanced);</li>
                        <li>Geolocation-based access controls and IP reputation filtering;</li>
                        <li>Rate limiting and throttling mechanisms;</li>
                        <li>Bot detection and CAPTCHA implementation;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Network Security Layer:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Virtual Private Cloud (VPC) with isolated subnets;</li>
                        <li>Network segmentation separating production, staging, and development environments;</li>
                        <li>Private subnets for database and sensitive data processing;</li>
                        <li>Network Access Control Lists (NACLs) and Security Groups;</li>
                        <li>Intrusion Detection System (IDS) and Intrusion Prevention System (IPS);</li>
                        <li>Next-Generation Firewall (NGFW) with deep packet inspection;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Application Security Layer:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Secure coding practices following OWASP Secure Coding Guidelines;</li>
                        <li>Input validation and output encoding;</li>
                        <li>Parameterized queries preventing SQL injection;</li>
                        <li>Cross-Site Scripting (XSS) protection;</li>
                        <li>Cross-Site Request Forgery (CSRF) token validation;</li>
                        <li>Secure session management with token rotation;</li>
                        <li>Content Security Policy (CSP) headers;</li>
                        <li>HTTP Strict Transport Security (HSTS);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(d) Data Security Layer:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Encryption at rest using AES-256-GCM;</li>
                        <li>Encryption in transit using TLS 1.3 (minimum TLS 1.2);</li>
                        <li>Database-level encryption with transparent data encryption (TDE);</li>
                        <li>Field-level encryption for highly sensitive data (PAN, Aadhaar, financial data);</li>
                        <li>Secure key management using Hardware Security Modules (HSM) or AWS KMS;</li>
                        <li>Regular key rotation (90-day cycle for sensitive operations);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(e) Endpoint Security Layer:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Endpoint detection and response (EDR) solutions;</li>
                        <li>Antivirus and anti-malware software on all endpoints;</li>
                        <li>Mobile device management (MDM) for BYOD policies;</li>
                        <li>Device encryption enforcement;</li>
                        <li>Remote wipe capabilities for lost/stolen devices;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(f) Identity and Access Management Layer:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Role-Based Access Control (RBAC) with principle of least privilege;</li>
                        <li>Multi-Factor Authentication (MFA) mandatory for all administrative access;</li>
                        <li>Single Sign-On (SSO) with SAML 2.0/OAuth 2.0/OpenID Connect;</li>
                        <li>Privileged Access Management (PAM) for critical systems;</li>
                        <li>Regular access reviews and privilege de-escalation;</li>
                        <li>Automated de-provisioning upon employment termination;</li>
                        <li>Session timeout policies (15 minutes inactivity for sensitive operations);</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.2.2 Encryption Standards and Implementation:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Data at Rest Encryption:</p>
                    <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                        <table className="w-full text-sm text-left text-slate-600">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-3">Data Category</th>
                                    <th className="px-6 py-3">Encryption Standard</th>
                                    <th className="px-6 py-3">Key Management</th>
                                    <th className="px-6 py-3">Rotation Frequency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Sensitive Personal Data (SPDI)</td><td className="px-6 py-4">AES-256-GCM</td><td className="px-6 py-4">AWS KMS with HSM backing</td><td className="px-6 py-4">90 days</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Payment Information</td><td className="px-6 py-4">AES-256 + Tokenization</td><td className="px-6 py-4">PCI-DSS compliant vault</td><td className="px-6 py-4">180 days</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">KYC Documents</td><td className="px-6 py-4">AES-256-CBC</td><td className="px-6 py-4">Separate KMS instance</td><td className="px-6 py-4">365 days</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Transaction Records</td><td className="px-6 py-4">AES-256-GCM</td><td className="px-6 py-4">AWS KMS with CloudHSM</td><td className="px-6 py-4">90 days</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Analytics/Cookie Data</td><td className="px-6 py-4">AES-256-GCM</td><td className="px-6 py-4">Standard KMS</td><td className="px-6 py-4">180 days</td></tr>
                                <tr className="bg-white"><td className="px-6 py-4">Backup Data</td><td className="px-6 py-4">AES-256-GCM</td><td className="px-6 py-4">Offline key storage</td><td className="px-6 py-4">Annual</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Data in Transit Encryption:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>External Communications:</strong> TLS 1.3 (preferred) or TLS 1.2 (minimum); Perfect Forward Secrecy (PFS) enabled; Strong cipher suites only (no RC4, 3DES, or export ciphers); HSTS with preloading enabled; Certificate pinning for mobile applications; Regular SSL/TLS configuration audits using SSL Labs;</li>
                        <li>(ii) <strong>Internal Communications:</strong> IPsec VPN tunnels between data centers; TLS for microservices communication; Mutual TLS (mTLS) for service-to-service authentication; Encrypted database connections;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Encryption Key Management:</p>
                    <ul className="list-none pl-4 text-slate-600 space-y-1">
                        <li>(i) <strong>Key Generation:</strong> Cryptographically secure random number generation; Minimum 256-bit key length; Hardware Security Module (HSM) or AWS CloudHSM for key generation;</li>
                        <li>(ii) <strong>Key Storage:</strong> Master keys stored in FIPS 140-2 Level 3 compliant HSM; Data encryption keys (DEK) encrypted by key encryption keys (KEK) - envelope encryption; Key material never exposed in plaintext outside HSM; Separate keys for production, staging, and development environments;</li>
                        <li>(iii) <strong>Key Rotation:</strong> Automated rotation per schedule above; Emergency rotation capability for compromised keys; Old keys retained for decryption of historical data only; Audit trail of all key operations;</li>
                        <li>(iv) <strong>Key Destruction:</strong> Cryptographic deletion upon key retirement; Secure overwriting of key material; Documentation of destruction for compliance audits;</li>
                    </ul>
                </div>
            </div>
        </div>
      </div>

      {/* 6.3 */}
      <div id="point-6-3" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Key className="text-indigo-600" size={20} /> 6.3 ACCESS CONTROL AND AUTHENTICATION MECHANISMS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.3.1 Multi-Factor Authentication (MFA):</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>User Authentication:</strong> MFA mandatory for: high-value transactions ({'>'}INR 1 lakh), account settings changes, KYC updates, beneficiary additions; Supported factors: SMS OTP, Email OTP, TOTP authenticator apps (Google Authenticator, Authy), Hardware tokens (YubiKey), Biometric authentication (for mobile apps); Risk-based authentication triggering step-up authentication for suspicious activities;</li>
                <li>(b) <strong>Administrative Authentication:</strong> MFA mandatory for all administrative and privileged accounts; Hardware token required for production environment access; Separate authentication for database access; Biometric authentication for data center physical access;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.3.2 Password Policy:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Complexity Requirements:</strong> Minimum 12 characters (16 recommended); Combination of uppercase, lowercase, numbers, and special characters; No common words, dictionary words, or predictable patterns; No reuse of last 10 passwords;</li>
                <li>(b) <strong>Storage and Transmission:</strong> Passwords hashed using bcrypt (cost factor 12) or Argon2; Salted hashes with unique salt per user; Never stored or transmitted in plaintext; Password reset links expire within 1 hour;</li>
                <li>(c) <strong>Account Lockout:</strong> Automatic lockout after 5 failed login attempts; Progressive delays (exponential backoff); CAPTCHA after 3 failed attempts; Security team notification for repeated lockouts;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.3.3 Session Management:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Session Creation:</strong> Cryptographically secure session IDs (minimum 128-bit entropy); New session ID generated upon authentication; Session ID never exposed in URL;</li>
                <li>(b) <strong>Session Lifecycle:</strong> Absolute timeout: 8 hours; Idle timeout: 15 minutes for sensitive operations, 30 minutes for general browsing; Explicit logout destroys session immediately; Single session per user (concurrent sessions prohibited for sensitive accounts);</li>
                <li>(c) <strong>Session Security:</strong> HttpOnly flag preventing JavaScript access; Secure flag ensuring HTTPS-only transmission; SameSite=Strict attribute preventing CSRF; Session fixation protection through ID regeneration;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.3.4 Role-Based Access Control (RBAC):</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Defined Roles:</p>
                    <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                        <table className="w-full text-sm text-left text-slate-600">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-3">Role</th>
                                    <th className="px-6 py-3">Access Level</th>
                                    <th className="px-6 py-3">Permissions</th>
                                    <th className="px-6 py-3">MFA Required</th>
                                    <th className="px-6 py-3">Review Frequency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Super Administrator</td><td className="px-6 py-4">Full system access</td><td className="px-6 py-4">All operations</td><td className="px-6 py-4">Hardware token</td><td className="px-6 py-4">Monthly</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Security Administrator</td><td className="px-6 py-4">Security systems</td><td className="px-6 py-4">Logging, monitoring, incident response</td><td className="px-6 py-4">Hardware token</td><td className="px-6 py-4">Monthly</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Database Administrator</td><td className="px-6 py-4">Database systems</td><td className="px-6 py-4">Schema changes, data access</td><td className="px-6 py-4">Hardware token + approval</td><td className="px-6 py-4">Monthly</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Application Developer</td><td className="px-6 py-4">Development environments</td><td className="px-6 py-4">Code deployment (non-prod)</td><td className="px-6 py-4">Software token</td><td className="px-6 py-4">Quarterly</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Customer Support</td><td className="px-6 py-4">User accounts (limited)</td><td className="px-6 py-4">View profiles, reset passwords</td><td className="px-6 py-4">Software token</td><td className="px-6 py-4">Quarterly</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Compliance Officer</td><td className="px-6 py-4">Audit logs, reports</td><td className="px-6 py-4">Read-only access</td><td className="px-6 py-4">Software token</td><td className="px-6 py-4">Quarterly</td></tr>
                                <tr className="bg-white"><td className="px-6 py-4">Investment Adviser</td><td className="px-6 py-4">Investment data</td><td className="px-6 py-4">Recommendations, client profiles</td><td className="px-6 py-4">Software token</td><td className="px-6 py-4">Quarterly</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Access Request and Approval Workflow:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Formal access request with business justification;</li>
                        <li>Manager approval required;</li>
                        <li>Security team verification;</li>
                        <li>Automated provisioning after approvals;</li>
                        <li>Temporary access automatically expires;</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Principle of Least Privilege:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Users granted minimum necessary permissions;</li>
                        <li>Just-in-time (JIT) access for elevated privileges;</li>
                        <li>Time-bound access for sensitive operations;</li>
                        <li>Regular privilege audits and de-escalation;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.3.5 Privileged Access Management (PAM):</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Bastion Hosts / Jump Servers:</strong> All production access routed through hardened bastion hosts; Session recording for all privileged sessions; No direct internet access to production systems;</li>
                <li>(b) <strong>Privileged Account Management:</strong> Separate privileged accounts (no shared credentials); Privileged passwords rotated every 30 days; Break-glass procedures for emergency access; Dual control for critical operations (two-person rule);</li>
                <li>(c) <strong>Monitoring and Alerting:</strong> Real-time alerts for privileged account usage; Anomaly detection for unusual privileged activities; Security Operations Center (SOC) monitoring 24/7;</li>
            </ul>
        </div>
      </div>

      {/* 6.4 */}
      <div id="point-6-4" className="subsection mb-10 scroll-mt-32">
        <h3 className="subsection-title font-serif text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <Server className="text-indigo-600" size={20} /> 6.4 LOGGING, MONITORING, AND AUDIT TRAILS
        </h3>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.4.1 Comprehensive Logging Framework:</h4>
            <div className="ml-4 space-y-4">
                <div>
                    <p className="text-slate-700 font-semibold">(a) Events Logged:</p>
                    <div className="overflow-x-auto mb-4 rounded-lg border border-slate-200">
                        <table className="w-full text-sm text-left text-slate-600">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-3">Event Category</th>
                                    <th className="px-6 py-3">Specific Events</th>
                                    <th className="px-6 py-3">Retention Period</th>
                                    <th className="px-6 py-3">Storage Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Authentication</td><td className="px-6 py-4">Login success/failure, MFA events, password changes</td><td className="px-6 py-4">3 years</td><td className="px-6 py-4">Encrypted S3 + SIEM</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Authorization</td><td className="px-6 py-4">Access grants/denials, privilege escalation</td><td className="px-6 py-4">3 years</td><td className="px-6 py-4">Encrypted S3 + SIEM</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Data Access</td><td className="px-6 py-4">SPDI access, bulk data exports, queries</td><td className="px-6 py-4">5 years (PMLA)</td><td className="px-6 py-4">Encrypted S3 + SIEM</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Configuration Changes</td><td className="px-6 py-4">System settings, security policy changes</td><td className="px-6 py-4">7 years</td><td className="px-6 py-4">Encrypted S3 + SIEM</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Security Events</td><td className="px-6 py-4">Firewall blocks, IDS alerts, failed attacks</td><td className="px-6 py-4">2 years</td><td className="px-6 py-4">SIEM</td></tr>
                                <tr className="bg-white border-b border-slate-100"><td className="px-6 py-4">Transactions</td><td className="px-6 py-4">Investment transactions, payments, KYC updates</td><td className="px-6 py-4">10 years (SEBI)</td><td className="px-6 py-4">Database + S3</td></tr>
                                <tr className="bg-white"><td className="px-6 py-4">Administrative Actions</td><td className="px-6 py-4">User account changes, role assignments</td><td className="px-6 py-4">5 years</td><td className="px-6 py-4">Encrypted S3 + SIEM</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(b) Log Attributes:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Timestamp (UTC with millisecond precision);</li>
                        <li>User identifier (pseudonymized where possible);</li>
                        <li>Source IP address;</li>
                        <li>Action performed;</li>
                        <li>Object/resource affected;</li>
                        <li>Result (success/failure);</li>
                        <li>Session identifier;</li>
                        <li>Geographic location (country/city);</li>
                    </ul>
                </div>
                <div>
                    <p className="text-slate-700 font-semibold">(c) Log Protection:</p>
                    <ul className="list-disc pl-6 text-slate-600 space-y-1">
                        <li>Write-once, read-many (WORM) storage;</li>
                        <li>Cryptographic hash chains preventing tampering;</li>
                        <li>Separate logging infrastructure with restricted access;</li>
                        <li>Regular integrity verification;</li>
                    </ul>
                </div>
            </div>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.4.2 Security Information and Event Management (SIEM):</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Real-Time Monitoring:</strong> Centralized log aggregation from all systems; Correlation of events across systems; Automated threat detection based on rules and machine learning; Real-time alerting for security incidents;</li>
                <li>(b) <strong>Use Cases and Rules:</strong> Brute force attack detection (multiple failed logins); Privilege escalation attempts; Data exfiltration detection (unusual data access patterns); Malware and ransomware indicators; Insider threat detection (abnormal user behavior); Compliance violations (accessing data without authorization);</li>
                <li>(c) <strong>SIEM Platform:</strong> Splunk Enterprise Security or equivalent; Integration with threat intelligence feeds; Automated incident response playbooks; Forensic investigation capabilities;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.4.3 Continuous Monitoring:</h4>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Infrastructure Monitoring:</strong> Real-time server performance metrics; Network traffic analysis; Database query performance and anomaly detection; API endpoint monitoring;</li>
                <li>(b) <strong>Security Monitoring:</strong> Vulnerability scanning (weekly automated scans); Configuration drift detection; Certificate expiration monitoring; Open port and service monitoring;</li>
                <li>(c) <strong>Compliance Monitoring:</strong> PCI-DSS compliance scanning; ISO 27001 control effectiveness monitoring; SEBI regulatory requirement tracking; Data retention policy enforcement;</li>
            </ul>
        </div>

        <div className="mb-6">
            <h4 className="text-lg font-bold text-slate-700 mb-2">6.4.4 Audit Trail Requirements:</h4>
            <p className="text-slate-600 mb-2">Pursuant to SEBI (Investment Advisers) Regulations, 2013, Regulation 16:</p>
            <ul className="list-none pl-4 text-slate-600 space-y-1">
                <li>(a) <strong>Investment Advisory Activities:</strong> Complete record of advice provided to each client; Risk profiling questionnaires and assessments; Investment recommendations with rationale; Client acknowledgments and consents; Periodic portfolio review reports;</li>
                <li>(b) <strong>Retention:</strong> Minimum 10 years from date of transaction or cessation of relationship;</li>
                <li>(c) <strong>Format:</strong> Electronic records with digital signatures/timestamps, readily accessible for SEBI inspection;</li>
            </ul>
        </div>
      </div>
    </section>
  );
}