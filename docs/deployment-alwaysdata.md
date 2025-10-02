# Alwaysdata Deployment Guide (Beginner Friendly)

> Follow these steps to deploy the Laravel application to the Alwaysdata free tier without Docker.

## Prerequisites
- Alwaysdata account with free plan activated.
- GitHub repository access and permission to create deploy keys.
- Local machine with PHP 8.3, Composer, and Git installed.
- PlanetScale and Upstash credentials (see `docs/accounts.md`).

## 0. Collect Alwaysdata Secrets for GitHub
1. Sign in to the [Alwaysdata admin panel](https://admin.alwaysdata.com/) and note the account name shown in the top-right corner or in the default site URL (for example, `acme` in `https://acme.alwaysdata.net`). Save this as the GitHub secret `ALWAYSDATA_ACCOUNT`.
2. Navigate to **Account → Users & API → API tokens** and click **Add**. Give the token a descriptive label (e.g., `GitHub Deploy`), click **Create**, and copy the generated value immediately. Store it securely and map it to the secret `ALWAYSDATA_API_TOKEN`.
3. Go to **Web → Sites**, open the Laravel site you created, and copy the numeric ID displayed in the page header or the browser URL (e.g., `/sites/123456`). Use this value for the secret `ALWAYSDATA_SITE_ID` if your automation calls the API for deploy hooks or restarts.
4. If your workflow uses SSH instead of the API, capture the SSH host (`ssh-<account>.alwaysdata.net`), SSH user (listed at the top of **Remote access → SSH**), and the private key associated with the deploy key. Store these as `ALWAYSDATA_SSH_HOST`, `ALWAYSDATA_SSH_USER`, and `ALWAYSDATA_DEPLOY_KEY` respectively.
5. In GitHub, open the repository and add each value under **Settings → Secrets and variables → Actions → New repository secret**. Paste the full token or private key block exactly as issued.

> Tip: Rotate the API token or SSH key from the Alwaysdata dashboard and update GitHub secrets immediately if the credentials are ever exposed or if an engineer leaves the project.

## 1. Create the Alwaysdata Site
1. Sign in to the [Alwaysdata admin panel](https://admin.alwaysdata.com/).
2. Navigate to **Web > Sites** and click **Create a site**.
3. Choose **Language = PHP**, select **Version 8.3**, and enable **Composer** support.
4. Set the **Root directory** to `www/wps-payroll/public` (Alwaysdata will create the folders auwpspayrollcompliance.alwaysdata.nettomatically).
5. Confirm the default Alwaysdata subdomain (e.g., ``) or attach a custom domain.

## 2. Configure SSH Access
1. Go to **Remote access > SSH** and add an **Authorized key**.
2. Paste the public key that matches the deploy key stored in GitHub (`ALWAYSDATA_DEPLOY_KEY`).
3. Note the SSH username displayed at the top of the page (format: `<account>-<login>`).

## 3. Enable Git Deployments
1. Still under **Remote access**, open **Git** and click **Add a repository**.
2. Provide a repository name (e.g., `wps-payroll`) and select **Shared account** if prompted.
3. Copy the git remote URL in the format `ssh://<account>@ssh.alwaysdata.com/<account>/<repository>.git`.
4. Add this URL to the GitHub repository as a deploy secret (`ALWAYSDATA_GIT_REMOTE`).

## 4. Prepare Environment Variables
1. Navigate to **Advanced > Environment variables**.
2. Add the required keys from `.env.example`, including:
   - `APP_KEY` (generate locally using `php artisan key:generate --show`).
   - `APP_URL`, `APP_ENV`, `LOG_CHANNEL`.
   - Database connection variables (`DATABASE_URL` or `DB_*`).
   - Queue/Redis variables (`REDIS_URL`, `REDIS_REST_URL`, `UPSTASH_REDIS_REST_TOKEN`).
   - `OPS_ADMIN_*` values used for the seeded Filament super admin.
3. Save the configuration; Alwaysdata applies changes immediately.

## 5. Set Up PlanetScale & Upstash Connections
1. In PlanetScale, create a passwordless connection string and paste it into the `DATABASE_URL` variable.
2. In Upstash, collect the TLS connection URL and REST token.
3. Back in Alwaysdata, update the corresponding environment variables and click **Save**.

## 6. Trigger the First Deployment
1. On your local machine, add the Alwaysdata remote:
   ```bash
   git remote add alwaysdata ssh://<account>@ssh.alwaysdata.com/<account>/<repository>.git
   ```
2. Push the main branch:
   ```bash
   git push alwaysdata master
   ```
3. Alternatively, run the GitHub `deploy.yml` workflow which performs the push automatically using the deploy key.

## 7. Install Dependencies on the Server
1. Connect via SSH: `ssh <account>@ssh.alwaysdata.com`.
2. Change to the project directory: `cd www/wps-payroll`.
3. Run Composer in production mode:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. Run Laravel optimizations:
   ```bash
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan migrate --force
   ```

## 8. Configure Scheduled Tasks (Optional)
1. Go to **Web > Tasks** in Alwaysdata.
2. Create a cron entry that runs `php artisan schedule:run` every minute.
3. Verify logs under **Logs > Scheduled tasks** after the first execution.

## 9. Verify the Deployment
1. Visit the Alwaysdata subdomain (e.g., `https://wpspayrollcompliance.alwaysdata.net`).
2. Open `/health` to ensure the health check passes.
3. Inspect **Logs > Web > Access/Error** for any issues during the first requests.
4. Confirm queues connect to Upstash by checking Horizon or application logs.

## 10. Troubleshooting Tips
- If you receive a 500 error, check `storage/logs/laravel.log` via SSH.
- Ensure file permissions allow `storage/` and `bootstrap/cache/` to be writable: `chmod -R 775 storage bootstrap/cache`.
- Regenerate the deploy key if GitHub fails to push and update the Alwaysdata authorized keys.
- Verify PHP extensions under **Web > Sites > Configuration**; enable `intl`, `bcmath`, and `pdo_mysql`.

You’re now ready to run the Laravel application on Alwaysdata without Docker.
