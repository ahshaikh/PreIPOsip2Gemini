// V-PHASE6-1730-133
# PreIPO SIP Platform (preiposip.com)

This repository contains the complete codebase for the PreIPO SIP Platform, a "Zero-Fee" Pre-IPO investment platform built on a headless architecture.

The business model is based on a **Bulk Purchase Model**, where the platform earns margins from sellers (via discounts and extra allocations) and passes a percentage of this margin to users as "Guaranteed Bonuses".

## 🏛️ Core Architecture

This is a monorepo containing two separate applications:

* **/backend/**: A **Laravel 11** API (PHP 8.3)
    * Handles all business logic, database, user auth, and financial calculations.
    * [cite_start]Provides a "100% Configurable" engine where every feature is controlled by the Admin Panel [cite: 240-241, 439].
* **/frontend/**: A **Next.js 14** application (App Router)
    * Provides the public website, the user dashboard, and the admin panel.
    * Is fully decoupled and consumes the Laravel API.

## ✨ Core Features

* [cite_start]**100% Configurable Engine:** Every module (registration, withdrawals, bonuses) can be toggled on/off from the admin panel [cite: 439-456].
* **Dynamic Plan Creation:** Admins can create and configure an unlimited number of investment plans.
* **7-Way Bonus System:** A (now simplified) bonus engine, including a baseline 10% bonus and referral multipliers.
* **Full KYC Workflow:** A complete flow for users to submit KYC and for admins to approve/reject.
* **Financial Engine:** Full-cycle logic for subscriptions, payments (Razorpay), wallet, and admin-approved withdrawals.

## 🚀 Getting Started

1.  **Read the Deployment Guide:**
    * For local setup: See `MASTER_DEPLOYMENT_GUIDE.md > Part 1`.
    * For production: See `MASTER_DEPLOYMENT_GUIDE.md > Part 2`.

2.  **Understand the API:**
    * See `MASTER_API_REFERENCE.md` for a list of key endpoints.

3.  **Review the Test Plan:**
    * See `MASTER_TEST_PLAN.md` for Unit, Integration, and UAT scenarios.