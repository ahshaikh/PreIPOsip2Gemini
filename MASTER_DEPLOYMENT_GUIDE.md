// V-PHASE6-1730-136
# Master Deployment Guide

This guide covers setup for local development and a production environment on DigitalOcean + Vercel.

---

## Part 1: Local Development Setup

### Prerequisites

* PHP 8.3+ & Composer
* Node.js 20+ & npm
* MySQL 8.0+
* Redis 6.0+

### 1. Backend (Laravel) Setup

1.  Navigate to the `/backend` directory:
    ```bash
    cd backend
    ```
2.  Install dependencies:
    ```bash
    composer install
    ```
3.  Set up your environment file:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
4.  Edit `.env` with your `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
5.  Run database migrations and seeders:
    ```bash
    php artisan migrate --seed
    ```

### 2. Frontend (Next.js) Setup

1.  Navigate to the `/frontend` directory:
    ```bash
    cd frontend
    ```
2.  Install dependencies:
    ```bash
    npm install
    ```
3.  Set up your local environment file:
    ```bash
    cp .env.local.example .env.local
    ```
    *(The default `NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1` is correct for local dev).*

### 3. Running the Application

You must run 3 separate processes in 3 terminals:

* **Terminal 1 (Backend API):**
    ```bash
    cd backend
    php artisan serve
    ```
* **Terminal 2 (Frontend App):**
    ```bash
    cd frontend
    npm run dev
    ```
* **Terminal 3 (Queue Worker):**
    ```bash
    cd backend
    php artisan queue:work
    ```

You can now access the site at `http://localhost:3000`.

---

## Part 2: Production Deployment (Vercel + DigitalOcean)

This is the recommended, highly scalable production setup.

* **Frontend (Next.js):** Deployed to **Vercel**.
* **Backend (Laravel):** Deployed to a **DigitalOcean Droplet**.

### 1. Backend (DigitalOcean Droplet)

1.  **Provision Droplet:**
    * Create a DigitalOcean Droplet: **Ubuntu 22.04**, 2GB RAM, 1vCPU.
    * Install the **LEMP** stack (Nginx, MySQL, PHP 8.3).
    * Install **Redis**, **Composer**, **Git**, and **Supervisor**.

2.  **Deploy Code:**
    * Clone your repo to `/var/www/preiposip`.
    * `cd /var/www/preiposip/backend`
    * `composer install --no-dev --optimize-autoloader`

3.  **Configure Environment:**
    * `cp .env.example .env`
    * `php artisan key:generate`
    * Edit `.env`:
        * `APP_ENV=production`
        * `APP_DEBUG=false`
        * `APP_URL=https://api.preiposip.com` (Your API subdomain)
        * `FRONTEND_URL=https://preiposip.com` (Your main domain)
        * `SANCTUM_STATEFUL_DOMAINS=preiposip.com`
        * Fill in all `DB_`, `REDIS_`, `RAZORPAY_` keys.

4.  **Configure Nginx:**
    * Create an Nginx config in `/etc/nginx/sites-available/api.preiposip.com`.
    * Point the `root` to `/var/www/preiposip/backend/public`.
    * Add the standard Laravel PHP-FPM processing block.
    * `ln -s ...` to `sites-enabled`.

5.  **Run Setup:**
    * `php artisan migrate --force`
    * `php artisan config:cache`
    * `php artisan route:cache`
    * `sudo chown -R www-data:www-data /var/www/preiposip/backend`

6.  **Configure Queue Worker:**
    * Create a Supervisor config in `/etc/supervisor/conf.d/preiposip-worker.conf`.
    * `command=php /var/www/preiposip/backend/artisan queue:work redis --tries=3`
    * `sudo supervisorctl reread && sudo supervisorctl update`

7.  **Secure with SSL:**
    * `sudo certbot --nginx -d api.preiposip.com`

### 2. Frontend (Vercel)

1.  **Import Project:**
    * Log in to Vercel and import your Git repository.
    * Select the `/frontend` directory as the **Root Directory**.

2.  **Configure Settings:**
    * **Build Command:** `npm run build`
    * **Start Command:** `npm run start`

3.  **Add Environment Variables:**
    * `NEXT_PUBLIC_API_URL`: `https://api.preiposip.com/api/v1` (The URL of your live backend).

4.  **Deploy:**
    * Vercel will build and deploy the site. Assign your `preiposip.com` domain to this project.

### 3. Replit Setup

While Replit is excellent for development, it is **not recommended** for a production financial application. It lacks the persistent storage (MySQL), dedicated in-memory cache (Redis), and robust background queue processing (Supervisor) required for this project.