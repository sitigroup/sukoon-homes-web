# Trust Verification API QA — Runbook (Task 11 / 11B)

Production-safe Postman/Newman kit for `/api/trust-verification`.

## Files

| File | Purpose |
|------|---------|
| `Sukoon-Trust-Verification.postman_collection.json` | API tests (5 top-level folders) |
| `Sukoon-Production.postman_environment.json` | Production host + safety flags |
| `Sukoon-Local.postman_environment.json` | Localhost |
| `samples/` | Upload fixtures |
| `build-trust-verification-collection.py` | Regenerate JSON |
| `run-regression.sh` / `run-regression.ps1` | **Safe Regression only** (default) |
| `run-destructive.sh` | Destructive folders (staging/local only) |

## Collection folders (Task 11B)

| Folder | Production? | `run_destructive_tests` |
|--------|-------------|-------------------------|
| **Safe Regression** | Yes — **only this by default** | `false` (default) |
| Destructive Tests | No | must be `true` |
| Rate Limit Tests | **Never on production** | must be `true` |
| Webhook Manual Tests | Manual / staging | `true` + `cashfree_webhook_secret` |
| Admin Session Tests | Staging / browser QA | must be `true` |

## Safety variables

| Variable | Production default | Purpose |
|----------|-------------------|---------|
| `run_destructive_tests` | **`false`** | Skips destructive/rate-limit/webhook/admin folders |
| `request_delay_ms` | `1500` | Documented delay; use Runner delay or Newman `--delay-request` |
| `customer_password` | (empty) | Set in Postman only — never commit |

## Never on production

- Running the **full collection** (all folders)
- **Rate Limit Tests** folder (triggers 429 / hour lockout)
- **Destructive Tests** with `run_destructive_tests=true` unless you accept extra orders + limits
- Looping payment-intent or create-order requests

## Safe production run (Postman)

1. Re-import collection + **Sukoon Production** environment.
2. Confirm **`run_destructive_tests`** = `false`.
3. Set **`customer_password`** (phone login account).
4. Open collection → **Run**.
5. Select **only** folder **Safe Regression**.
6. Set **Delay** = **1500 ms** between requests.
7. On step **4. POST upload document** → attach `samples/qa-id-front.jpg` if needed.
8. Run.

**Do not** check other folders. **Do not** use “Run entire collection”.

## Safe production run (Newman)

From `web-fix/postman/`:

```powershell
$env:TV_CUSTOMER_PASSWORD = 'your-password'
.\run-regression.ps1 -Environment Production
```

```bash
export TV_CUSTOMER_PASSWORD='your-password'
./run-regression.sh production
```

This runs **Safe Regression** only, forces `run_destructive_tests=false`, and `--delay-request 1500`.

## Safe Regression steps (8)

1. Login (phone)  
2. GET packages  
3. POST create order — **one** order per run (5/hour user limit)  
4. POST upload document  
5. POST payment-intent — **max once per order** (auto-skips if re-run same `order_id`)  
6. POST confirm-payment — once  
7. GET order  
8. GET report download (404 OK if admin has not uploaded PDF)

**No webhooks** in Safe Regression.

## Destructive / staging runs

1. Use **Sukoon Local** or a staging admin host.
2. Set **`run_destructive_tests`** = **`true`**.
3. Run one folder at a time (not full collection).
4. Optional: `cashfree_webhook_secret` for **Webhook Manual Tests** signed requests.

```bash
./run-destructive.sh Sukoon-Local.postman_environment.json
```

## Payment-intent guard

After a successful payment-intent for an `order_id`, the collection sets `tv_payment_intent_used_{order_id}=true`. Further payment-intent calls for that order are **skipped** (avoids 5/hour per-order limit on re-runs).

Clear by using a **new order** (step 3) or delete the env key in Postman.

## Webhook manual rules

- Not included in Safe Regression.  
- Signed webhooks run only when **`run_destructive_tests=true`** AND **`cashfree_webhook_secret`** is set.  
- Run **payment-intent** first to populate `cashfree_link_id`.

## Rate limits (reference)

See `web-fix/TASK-9-RATE-LIMITING.md`. Example: **5 orders / hour / user**.

If you see:

```json
{"error":true,"message":"Too many attempts. Please try again later.","retry_after":3050}
```

Wait `retry_after` seconds (~51 min for 3050) before retrying.

## Delay recommendation

| Tool | Setting |
|------|---------|
| Postman Collection Runner | **Delay** 1000–2000 ms (use **1500**) |
| Newman | `--delay-request 1500` or `TV_REQUEST_DELAY_MS=2000` |

## Regenerate collection

```bash
python3 web-fix/postman/build-trust-verification-collection.py
```

(On Windows without Python, run the script on the production server in `/tmp/tv-postman` and copy JSON back.)

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 429 Too many attempts | Wait `retry_after`; run **Safe Regression** only once per hour |
| Upload 422 | Attach `samples/qa-id-front.jpg` |
| Webhook 403 | Set `cashfree_webhook_secret` or skip webhook folder |
| Destructive tests still run | Set `run_destructive_tests=false` |
| Email login fails | Use **phone login** (step 1 in Safe Regression) |
| `documents//download` | Upload failed → `document_id` empty |

## Related docs

- `web-fix/TASK-9-RATE-LIMITING.md`
- `web-fix/TASK-7-CASHFREE-WEBHOOK.md`
