#!/usr/bin/env python3
"""Generate Postman collection + environments — Task 11 / 11B production-safe."""
import json
from pathlib import Path

OUT = Path(__file__).parent

COLLECTION_VARS = [
    {"key": "base_url", "value": "https://admin-homes.sukoon.group"},
    {"key": "api_prefix", "value": "/api/trust-verification"},
    {"key": "run_destructive_tests", "value": "false"},
    {"key": "request_delay_ms", "value": "1500"},
    {"key": "auth_token", "value": ""},
    {"key": "customer_id", "value": ""},
    {"key": "order_id", "value": ""},
    {"key": "order_number", "value": ""},
    {"key": "package_id", "value": ""},
    {"key": "document_id", "value": ""},
    {"key": "payment_transaction_id", "value": ""},
    {"key": "cashfree_link_id", "value": ""},
    {"key": "cashfree_test_order", "value": ""},
    {"key": "admin_token", "value": ""},
    {"key": "cashfree_webhook_secret", "value": ""},
    {"key": "tv_webhook_secret", "value": ""},
    {"key": "other_customer_token", "value": ""},
    {"key": "deleted_document_id", "value": ""},
    {"key": "customer_email", "value": "hemssarda@gmail.com"},
    {"key": "customer_password", "value": ""},
    {"key": "customer_mobile", "value": "9990687827"},
    {"key": "customer_country_code", "value": "91"},
]

COMMON_HEADERS = [{"key": "Accept", "value": "application/json"}]

AUTH_HEADER_SCRIPT = """
if (pm.environment.get('auth_token') || pm.collectionVariables.get('auth_token')) {
    const t = pm.environment.get('auth_token') || pm.collectionVariables.get('auth_token');
    pm.request.headers.upsert({ key: 'Authorization', value: 'Bearer ' + t });
}
"""

GUARD_DESTRUCTIVE = """
const v = String(pm.environment.get('run_destructive_tests') || pm.collectionVariables.get('run_destructive_tests') || 'false').toLowerCase();
if (!['true', '1', 'yes'].includes(v)) {
    if (pm.execution && typeof pm.execution.skipRequest === 'function') {
        pm.execution.skipRequest();
    }
}
"""

GUARD_WEBHOOK_MANUAL = GUARD_DESTRUCTIVE + """
const secret = (pm.environment.get('cashfree_webhook_secret') || '').trim();
if (!secret) {
    if (pm.execution && typeof pm.execution.skipRequest === 'function') {
        pm.execution.skipRequest();
    }
}
"""

WEBHOOK_SIGN_PREREQ = """
const secret = pm.environment.get('cashfree_webhook_secret') || '';
const ts = Math.floor(Date.now() / 1000).toString();
const payload = pm.variables.replaceIn(pm.request.body.raw);
const sig = CryptoJS.HmacSHA256(ts + payload, secret).toString(CryptoJS.enc.Base64);
pm.request.headers.upsert({ key: 'x-webhook-timestamp', value: ts });
pm.request.headers.upsert({ key: 'x-webhook-signature', value: sig });
"""

PAYMENT_INTENT_ONCE_PREREQ = AUTH_HEADER_SCRIPT + """
const oid = String(pm.environment.get('order_id') || '');
const key = 'tv_payment_intent_used_' + oid;
if (oid && pm.environment.get(key) === 'true') {
    if (pm.execution && typeof pm.execution.skipRequest === 'function') {
        pm.execution.skipRequest();
    }
}
"""

PAYMENT_INTENT_ONCE_MARK = """
if (pm.response.code === 200) {
  const oid = String(pm.environment.get('order_id') || '');
  if (oid) pm.environment.set('tv_payment_intent_used_' + oid, 'true');
}
"""

SKIP_DESTRUCTIVE_TEST = """
const v = String(pm.environment.get('run_destructive_tests') || 'false').toLowerCase();
if (!['true', '1', 'yes'].includes(v)) {
    pm.test('Skipped — run_destructive_tests is not true', () => pm.expect(true).to.eql(true));
}
"""

SKIP_WEBHOOK_TEST = SKIP_DESTRUCTIVE_TEST + """
const secret = (pm.environment.get('cashfree_webhook_secret') || '').trim();
if (!['true', '1', 'yes'].includes(String(pm.environment.get('run_destructive_tests') || 'false').toLowerCase()) || !secret) {
    pm.test('Skipped — set run_destructive_tests=true and cashfree_webhook_secret', () => pm.expect(true).to.eql(true));
}
"""

SKIP_PAYMENT_INTENT_TEST = """
const oid = String(pm.environment.get('order_id') || '');
if (oid && pm.environment.get('tv_payment_intent_used_' + oid) === 'true' && pm.response.code === 0) {
    pm.test('Skipped — payment-intent already run for this order_id', () => pm.expect(true).to.eql(true));
}
"""


def req(name, method, path, body=None, formdata=None, headers=None, tests=None, prerequest=None, desc=""):
    if not path.startswith("/"):
        path = "/api/trust-verification/" + path.lstrip("/")
    url = "{{base_url}}" + path
    r = {
        "name": name,
        "request": {
            "method": method,
            "header": list(COMMON_HEADERS) + (headers or []),
            "url": url,
        },
    }
    if desc:
        r["request"]["description"] = desc
    if body is not None:
        r["request"]["header"].append({"key": "Content-Type", "value": "application/json"})
        r["request"]["body"] = {"mode": "raw", "raw": json.dumps(body, indent=2)}
    if formdata:
        r["request"]["body"] = {"mode": "formdata", "formdata": formdata}
    events = []
    if prerequest:
        events.append({"listen": "prerequest", "script": {"exec": prerequest.strip().split("\n"), "type": "text/javascript"}})
    if tests:
        events.append({"listen": "test", "script": {"exec": tests.strip().split("\n"), "type": "text/javascript"}})
    if events:
        r["event"] = events
    return r


def guarded_req(*args, prerequest=None, tests=None, **kwargs):
    """Wrap request with destructive-test guard (prerequest + test skip markers)."""
    pre = GUARD_DESTRUCTIVE + "\n" + (prerequest or "")
    tst = SKIP_DESTRUCTIVE_TEST + "\n" + (tests or "")
    return req(*args, prerequest=pre, tests=tst, **kwargs)


def folder(name, items, desc=""):
    f = {"name": name, "item": items}
    if desc:
        f["description"] = desc
    return f


LOGIN_TESTS = """
pm.test('Status 200', () => pm.response.to.have.status(200));
const j = pm.response.json();
pm.test('No API error', () => pm.expect(j.error).to.eql(false));
pm.test('Token present', () => pm.expect(j.token).to.be.a('string').and.not.empty);
pm.environment.set('auth_token', j.token);
if (j.data && j.data.id) pm.environment.set('customer_id', String(j.data.id));
pm.collectionVariables.set('auth_token', j.token);
"""

CREATE_ORDER_TESTS = """
pm.test('Status 201', () => pm.response.to.have.status(201));
const j = pm.response.json();
pm.test('error false', () => pm.expect(j.error).to.eql(false));
pm.test('order_number present', () => pm.expect(j.data.order_number).to.match(/^TV-/));
pm.environment.set('order_id', String(j.data.id));
pm.environment.set('order_number', j.data.order_number);
pm.environment.set('cashfree_test_order', String(j.data.id));
pm.collectionVariables.set('order_id', String(j.data.id));
pm.environment.unset('tv_payment_intent_used_' + j.data.id);
"""

ERROR_422 = """
pm.test('Status 422', () => pm.response.to.have.status(422));
pm.test('error true', () => pm.expect(pm.response.json().error).to.eql(true));
"""

PUBLIC_OK = """
pm.test('Status 200', () => pm.response.to.have.status(200));
pm.test('error false', () => pm.expect(pm.response.json().error).to.eql(false));
"""

PACKAGE_SAVE = """
pm.test('Status 200', () => pm.response.to.have.status(200));
const pkgs = pm.response.json().data;
if (pkgs && pkgs.length) {
  const p = pkgs.find(x => x.slug && x.slug.includes('tenant-standard')) || pkgs[0];
  pm.environment.set('package_id', String(p.id));
  pm.collectionVariables.set('package_id', String(p.id));
}
"""

UPLOAD_DOC_TESTS = """
if (pm.response.code === 422) {
  const msg = (pm.response.json().message || '').toLowerCase();
  pm.test('Hint: attach samples/qa-id-front.jpg in Body → form-data → file', () => {
    pm.expect(msg).to.include('__never__');
  });
}
pm.test('Status 200', () => pm.response.to.have.status(200));
const j = pm.response.json();
pm.test('error false', () => pm.expect(j.error).to.eql(false));
if (j.data && j.data.id) {
  pm.environment.set('document_id', String(j.data.id));
}
"""

PAYMENT_INTENT_TESTS = PAYMENT_INTENT_ONCE_MARK + """
pm.test('Status 200', () => pm.response.to.have.status(200));
const j = pm.response.json();
if (j.data && j.data.payment_transaction_id) {
  pm.environment.set('payment_transaction_id', String(j.data.payment_transaction_id));
}
const linkId = j.data && j.data.payment_intent && j.data.payment_intent.id;
if (linkId) pm.environment.set('cashfree_link_id', String(linkId));
"""

VALID_ORDER_BODY = {
    "package_id": "{{package_id}}",
    "city_slug": "barmer",
    "notes": "Postman Safe Regression (Task 11B)",
    "subject": {
        "full_name": "Postman QA Tenant",
        "phone": "9876501234",
        "email": "qa-tv-postman@example.com",
        "current_address": "Barmer QA",
        "id_type": "Aadhaar",
        "id_number": "1234-5678-9012",
        "consent_given": True,
    },
}

# --- 1. Safe Regression (default production run) ---
safe_regression = folder(
    "Safe Regression",
    [
        req(
            "1. Login (phone)",
            "POST",
            "/api/user_signup",
            {
                "mobile": "{{customer_mobile}}",
                "country_code": "{{customer_country_code}}",
                "password": "{{customer_password}}",
                "type": "1",
                "logintype": "1",
            },
            tests=LOGIN_TESTS,
            desc="Production-safe. Set customer_password in Sukoon Production environment.",
        ),
        req(
            "2. GET packages",
            "GET",
            "/api/trust-verification/packages?type=tenant&city=barmer",
            tests=PACKAGE_SAVE,
            prerequest=AUTH_HEADER_SCRIPT,
        ),
        req(
            "3. POST create order (one per run)",
            "POST",
            "/api/trust-verification/orders",
            VALID_ORDER_BODY,
            tests=CREATE_ORDER_TESTS,
            prerequest=AUTH_HEADER_SCRIPT,
            desc="Creates exactly one order. Limit: 5/hour per user on production.",
        ),
        req(
            "4. POST upload document",
            "POST",
            "/api/trust-verification/orders/{{order_id}}/documents",
            formdata=[
                {"key": "doc_type", "value": "id_front", "type": "text"},
                {"key": "file", "type": "file", "src": ["samples/qa-id-front.jpg"]},
            ],
            tests=UPLOAD_DOC_TESTS,
            prerequest=AUTH_HEADER_SCRIPT,
            desc="Attach samples/qa-id-front.jpg if Runner does not auto-attach.",
        ),
        req(
            "5. POST payment-intent (max once per order)",
            "POST",
            "/api/trust-verification/orders/{{order_id}}/payment-intent",
            {"payment_method": "cashfree", "platform": "web"},
            tests=PAYMENT_INTENT_TESTS,
            prerequest=PAYMENT_INTENT_ONCE_PREREQ,
            desc="Skipped on re-run if tv_payment_intent_used_{order_id} is true. Limit: 5/hour per order.",
        ),
        req(
            "6. POST confirm-payment (once)",
            "POST",
            "/api/trust-verification/orders/{{order_id}}/confirm-payment",
            {"payment_transaction_id": "{{payment_transaction_id}}"},
            tests="""
pm.test('Status 200', () => pm.response.to.have.status(200));
""",
            prerequest=AUTH_HEADER_SCRIPT,
            desc="Does not complete Cashfree checkout — sync only. Safe if payment-intent ran once.",
        ),
        req(
            "7. GET order (read payment state)",
            "GET",
            "/api/trust-verification/orders/{{order_id}}",
            tests="""
pm.test('Status 200', () => pm.response.to.have.status(200));
const ps = pm.response.json().data.payment_status;
pm.test('payment pending or paid', () => pm.expect(['pending','paid']).to.include(ps));
""",
            prerequest=AUTH_HEADER_SCRIPT,
        ),
        req(
            "8. GET report download (read-only)",
            "GET",
            "/api/trust-verification/orders/{{order_id}}/report/download",
            tests="""
pm.test('Status 200 or 404 if no report yet', () => pm.expect(pm.response.code).to.be.oneOf([200, 404]));
""",
            prerequest=AUTH_HEADER_SCRIPT,
        ),
    ],
    "DEFAULT production run. Runner/Newman: this folder only. Set Collection Runner delay 1500ms (or Newman --delay-request 1500).",
)

# --- 2. Destructive Tests ---
destructive = folder(
    "Destructive Tests",
    [
        guarded_req(
            "Login — invalid password",
            "POST",
            "/api/user_signup",
            {"email": "{{customer_email}}", "password": "wrong-password-qa", "type": "3"},
            tests="""
pm.test('Status 400 or 401', () => pm.expect(pm.response.code).to.be.oneOf([400, 401, 422]));
pm.test('error true', () => pm.expect(pm.response.json().error).to.eql(true));
""",
        ),
        guarded_req(
            "POST order — missing consent",
            "POST",
            "/api/trust-verification/orders",
            {
                "package_id": "{{package_id}}",
                "city_slug": "barmer",
                "subject": {"full_name": "No Consent", "phone": "9876509999"},
            },
            tests=ERROR_422,
            prerequest=AUTH_HEADER_SCRIPT,
        ),
        guarded_req(
            "POST order — invalid package_id",
            "POST",
            "/api/trust-verification/orders",
            {
                "package_id": 999999,
                "subject": {"full_name": "Bad Pkg", "phone": "9876509998", "consent_given": True},
            },
            tests="""
pm.test('Status 404 or 422', () => pm.expect(pm.response.code).to.be.oneOf([404, 422]));
""",
            prerequest=AUTH_HEADER_SCRIPT,
        ),
        guarded_req(
            "POST cancel order",
            "POST",
            "/api/trust-verification/orders/{{order_id}}/cancel",
            {},
            tests="""
pm.test('Status 200', () => pm.response.to.have.status(200));
""",
            prerequest=AUTH_HEADER_SCRIPT,
        ),
        guarded_req(
            "POST upload — invalid doc_type",
            "POST",
            "/api/trust-verification/orders/{{order_id}}/documents",
            formdata=[{"key": "doc_type", "value": "not_a_real_type", "type": "text"}],
            tests=ERROR_422,
            prerequest=AUTH_HEADER_SCRIPT,
        ),
        guarded_req(
            "POST upload — fake PDF",
            "POST",
            "/api/trust-verification/orders/{{order_id}}/documents",
            formdata=[
                {"key": "doc_type", "value": "pan", "type": "text"},
                {"key": "file", "type": "file", "src": ["samples/fake.pdf"]},
            ],
            tests=ERROR_422,
            prerequest=AUTH_HEADER_SCRIPT,
        ),
        guarded_req(
            "POST upload — jpg.exe",
            "POST",
            "/api/trust-verification/orders/{{order_id}}/documents",
            formdata=[
                {"key": "doc_type", "value": "id_back", "type": "text"},
                {"key": "file", "type": "file", "src": ["samples/jpg.exe"]},
            ],
            tests=ERROR_422,
            prerequest=AUTH_HEADER_SCRIPT,
        ),
        guarded_req(
            "GET orders — no auth",
            "GET",
            "/api/trust-verification/orders",
            tests="""
pm.test('Status 401', () => pm.response.to.have.status(401));
""",
            prerequest="pm.request.headers.remove('Authorization');",
        ),
        guarded_req(
            "GET report — unauthorized token",
            "GET",
            "/api/trust-verification/orders/{{order_id}}/report/download",
            tests="""
pm.test('Status 401 or 403', () => pm.expect(pm.response.code).to.be.oneOf([401, 403]));
""",
            prerequest="""
pm.request.headers.upsert({ key: 'Authorization', value: 'Bearer invalid-token-qa' });
""",
        ),
        guarded_req(
            "GET download — deleted document",
            "GET",
            "/api/trust-verification/orders/{{order_id}}/documents/{{deleted_document_id}}/download",
            tests="""
pm.test('Status 404', () => pm.response.to.have.status(404));
""",
            prerequest=AUTH_HEADER_SCRIPT,
            desc="Set deleted_document_id first.",
        ),
        guarded_req(
            "POST create order (audit extra)",
            "POST",
            "/api/trust-verification/orders",
            {**VALID_ORDER_BODY, "notes": "Postman Destructive audit"},
            tests=CREATE_ORDER_TESTS,
            prerequest=AUTH_HEADER_SCRIPT,
            desc="Extra order for audit — counts toward 5/hour limit.",
        ),
    ],
    "Requires run_destructive_tests=true. Never run on production by default.",
)

# --- 3. Rate Limit Tests ---
rate_limits = folder(
    "Rate Limit Tests",
    [
        guarded_req(
            "POST create order (run 6× in Runner to trigger 429)",
            "POST",
            "/api/trust-verification/orders",
            {**VALID_ORDER_BODY, "notes": "Postman rate-limit probe"},
            tests="""
if (pm.response.code === 429) {
  pm.test('Rate limited', () => pm.response.to.have.status(429));
  pm.test('retry_after present', () => pm.expect(pm.response.json().retry_after).to.be.a('number'));
} else {
  pm.test('Under limit', () => pm.expect(pm.response.code).to.be.oneOf([201, 422]));
}
""",
            prerequest=AUTH_HEADER_SCRIPT,
            desc="NEVER on production unless run_destructive_tests=true. 6 quick runs → 429.",
        ),
    ],
    "Stress test only. Requires run_destructive_tests=true.",
)

# --- 4. Webhook Manual Tests ---
webhook_manual = folder(
    "Webhook Manual Tests",
    [
        req(
            "POST webhook — invalid signature",
            "POST",
            "/api/trust-verification/webhook/cashfree",
            {"type": "PAYMENT_SUCCESS_WEBHOOK", "data": {"link_id": "link_invalid"}},
            tests="""
""" + SKIP_DESTRUCTIVE_TEST + """
pm.test('Status 403', () => pm.response.to.have.status(403));
""",
            prerequest=GUARD_DESTRUCTIVE,
            headers=[
                {"key": "x-webhook-timestamp", "value": "1"},
                {"key": "x-webhook-signature", "value": "invalid"},
            ],
        ),
        req(
            "POST webhook — valid signature",
            "POST",
            "/api/trust-verification/webhook/cashfree",
            {"type": "PAYMENT_SUCCESS_WEBHOOK", "data": {"link_id": "{{cashfree_link_id}}"}},
            tests=SKIP_WEBHOOK_TEST + """
const secret = (pm.environment.get('cashfree_webhook_secret') || '').trim();
if (secret && ['true','1','yes'].includes(String(pm.environment.get('run_destructive_tests')||'false').toLowerCase())) {
  pm.test('Status 200 or 422', () => pm.expect(pm.response.code).to.be.oneOf([200, 422]));
}
""",
            prerequest=GUARD_WEBHOOK_MANUAL + "\n" + WEBHOOK_SIGN_PREREQ,
            desc="Requires run_destructive_tests=true AND cashfree_webhook_secret. Run payment-intent first.",
        ),
        req(
            "POST webhook — duplicate (run twice)",
            "POST",
            "/api/trust-verification/webhook/cashfree",
            {"type": "PAYMENT_SUCCESS_WEBHOOK", "data": {"link_id": "{{cashfree_link_id}}"}},
            tests=SKIP_WEBHOOK_TEST + """
const secret = (pm.environment.get('cashfree_webhook_secret') || '').trim();
if (secret && ['true','1','yes'].includes(String(pm.environment.get('run_destructive_tests')||'false').toLowerCase())) {
  pm.test('Status 200', () => pm.response.to.have.status(200));
}
""",
            prerequest=GUARD_WEBHOOK_MANUAL + "\n" + WEBHOOK_SIGN_PREREQ,
        ),
        req(
            "POST webhook — paid downgrade (valid sig)",
            "POST",
            "/api/trust-verification/webhook/cashfree",
            {"type": "PAYMENT_FAILED_WEBHOOK", "data": {"link_id": "{{cashfree_link_id}}"}},
            tests=SKIP_WEBHOOK_TEST + """
const secret = (pm.environment.get('cashfree_webhook_secret') || '').trim();
if (secret && ['true','1','yes'].includes(String(pm.environment.get('run_destructive_tests')||'false').toLowerCase())) {
  pm.test('Webhook accepted', () => pm.expect(pm.response.code).to.be.oneOf([200, 422]));
}
""",
            prerequest=GUARD_WEBHOOK_MANUAL + "\n" + WEBHOOK_SIGN_PREREQ,
            desc="Run only on already-paid orders.",
        ),
        guarded_req(
            "GET order — still paid after failed webhook",
            "GET",
            "/api/trust-verification/orders/{{order_id}}",
            tests="""
pm.test('Status 200', () => pm.response.to.have.status(200));
pm.test('Not failed', () => pm.expect(pm.response.json().data.payment_status).to.not.eql('failed'));
""",
            prerequest=AUTH_HEADER_SCRIPT,
        ),
    ],
    "Manual webhook QA. Requires run_destructive_tests=true and cashfree_webhook_secret for signed calls.",
)

# --- 5. Admin Session Tests ---
admin_session = folder(
    "Admin Session Tests",
    [
        guarded_req(
            "GET admin queue (no session)",
            "GET",
            "/trust-verification",
            tests="""
pm.test('Not open JSON API', () => pm.expect(pm.response.code).to.be.oneOf([200, 302, 401, 403, 404]));
""",
        ),
        guarded_req(
            "GET admin order (no session)",
            "GET",
            "/trust-verification/orders/1",
            tests="""
pm.test('Not open JSON API', () => pm.expect(pm.response.code).to.be.oneOf([200, 302, 401, 403, 404]));
""",
        ),
        guarded_req(
            "POST admin report upload (no session)",
            "POST",
            "/trust-verification/orders/{{order_id}}/report",
            tests="""
pm.test('Denied without session', () => pm.expect(pm.response.code).to.be.oneOf([302, 401, 403, 419, 404]));
""",
        ),
    ],
    "Browser session admin routes. Requires run_destructive_tests=true.",
)

items = [safe_regression, destructive, rate_limits, webhook_manual, admin_session]

collection = {
    "info": {
        "_postman_id": "sukoon-trust-verification-task11b",
        "name": "Sukoon Trust Verification API",
        "description": (
            "Task 11B — Production-safe QA kit.\n\n"
            "**Default run:** folder `Safe Regression` only.\n"
            "**Never** run the full collection on production.\n\n"
            "Set `run_destructive_tests=true` only on staging/local for Destructive, Rate Limit, Webhook Manual, Admin Session folders.\n\n"
            "Collection Runner: delay **1500 ms** between requests (`request_delay_ms`).\n"
            "Newman: `--delay-request 1500 --folder \"Safe Regression\"`."
        ),
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
    },
    "item": items,
    "variable": COLLECTION_VARS,
    "event": [
        {
            "listen": "prerequest",
            "script": {
                "type": "text/javascript",
                "exec": [
                    "const tv = pm.environment.get('auth_token') || pm.collectionVariables.get('auth_token');",
                    "const url = pm.request.url.toString();",
                    "if (tv && url.includes('/api/trust-verification') && !url.includes('webhook')) {",
                    "  pm.request.headers.upsert({ key: 'Authorization', value: 'Bearer ' + tv });",
                    "}",
                    "pm.request.headers.upsert({ key: 'Accept', value: 'application/json' });",
                ],
            },
        },
    ],
}

ENV_KEYS = [
    ("base_url", "https://admin-homes.sukoon.group", False, None),
    ("api_prefix", "/api/trust-verification", False, None),
    ("run_destructive_tests", "false", False, None),
    ("request_delay_ms", "1500", False, None),
    ("auth_token", "", False, None),
    ("customer_id", "15", False, None),
    ("order_id", "", False, None),
    ("package_id", "", False, None),
    ("cashfree_test_order", "", False, None),
    ("cashfree_link_id", "", False, None),
    ("admin_token", "", False, None),
    ("cashfree_webhook_secret", "", True, "secret"),
    ("tv_webhook_secret", "", True, "secret"),
    ("other_customer_token", "", False, None),
    ("customer_email", "hemssarda@gmail.com", False, None),
    ("customer_password", "", True, "secret"),
    ("customer_mobile", "9990687827", False, None),
    ("customer_country_code", "91", False, None),
    ("payment_transaction_id", "", False, None),
    ("order_number", "", False, None),
    ("document_id", "", False, None),
    ("deleted_document_id", "", False, None),
]


def build_env(env_id, name, base_url):
    values = []
    for key, val, _enabled, typ in ENV_KEYS:
        entry = {"key": key, "value": base_url if key == "base_url" else val, "enabled": True}
        if typ:
            entry["type"] = typ
        values.append(entry)
    return {"id": env_id, "name": name, "values": values, "_postman_variable_scope": "environment"}


prod_env = build_env("sukoon-tv-production", "Sukoon Production", "https://admin-homes.sukoon.group")
local_env = build_env("sukoon-tv-local", "Sukoon Local", "http://localhost:8000")
local_env["values"] = [v if v["key"] != "run_destructive_tests" else {**v, "value": "false"} for v in local_env["values"]]

OUT.joinpath("Sukoon-Trust-Verification.postman_collection.json").write_text(
    json.dumps(collection, indent=2), encoding="utf-8"
)
OUT.joinpath("Sukoon-Production.postman_environment.json").write_text(
    json.dumps(prod_env, indent=2), encoding="utf-8"
)
OUT.joinpath("Sukoon-Local.postman_environment.json").write_text(
    json.dumps(local_env, indent=2), encoding="utf-8"
)
print("Wrote Task 11B collection + environments to", OUT)
