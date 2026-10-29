# Taskforce visit history

Taskforce keeps one summary row per assigned outlet. Each **Tambah Visit** appends a separate visit with its date, findings, follow-up, and the signed-in user. Uploaded cells are unchanged. Visits remain attached to the outlet ID across replacement uploads and taskforce months. Optional visit-date filters apply equally to summaries, history, and workbook downloads.

A UUID makes retries idempotent. A deliberate second visit on the same day uses a new UUID. The latest summary uses the visit date (then ID to break a same-day tie), so recording a backdated visit cannot move the latest visit backward. Existing confirmed action edits are backfilled once per outlet/date using the latest saved note. The original action audit is retained in full.

All authenticated SURYA roles can record visits inside their existing TAP scope. History and exports enforce the same scope. The status is derived from the presence of visits, not a user-editable switch; it does not mean the outlet's task is complete.

## Production activation

Run in the existing checkout as the deploy account. These commands do not truncate or drop existing tables. The migration adds `taskforce_visits` and backfills accepted legacy visits. It can resume safely if a previous backfill was interrupted.

```sh
cd /var/www/inventoryapp
git pull --ff-only
sudo -u www-data php artisan migrate --path=database/migrations/2026_10_29_000001_create_taskforce_visit_history.php --force
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:cache
```

If code arrives before the migration, the page shows a preparation state, and new history endpoints return 503 rather than breaking with a missing-table 500. New visit entry is disabled until the table is ready.

Verify with an account in each required TAP scope: add two visits, confirm both notes in history and the latest date/count in the table, retry a request with the same UUID, and download **Ringkasan Outlet** / **Riwayat Visit**. Changing the date range must filter both sheets; replacing the assignment upload must preserve visit history for matching IDs. Deployment has not been verified until these checks succeed on the server.

## Export

The XLSX writer writes worksheet XML to private temporary files and queries in batches of 500. This avoids retaining an entire workbook in PHP memory. Literal strings cannot become spreadsheet formulas, IDs retain their text representation, and quantity/date cells retain numeric types. Temporary worksheet files are removed on success or failure; the final ZIP is deleted after sending.

Local benchmark: 10,000 outlet rows with 60 uploaded columns, plus 10,000 history rows, produced a valid two-sheet XLSX with 10,004 XML rows per sheet in about 3.4 seconds and 34 MB peak PHP memory under a 64 MB limit. This is a local benchmark, not a production latency guarantee.

The mobile sales/check-in modules are unchanged.
