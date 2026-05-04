# VIABIX Production Readiness Roadmap

**Status Atual:** 5.3/10 (NOT READY FOR PRODUCTION)  
**Target:** 8.2/10 (Production Ready)  
**Timeline Estimado:** 3-4 meses  
**Esforço:** 620 horas (2 devs, 3-4 meses)

---

## Timeline Visual

```
MAY 2026                                SEPTEMBER 2026
├─────────┬─────────┬─────────┬─────────┤
Phase 1   │  Phase 2  │  Phase 3  │  Launch
(2-3 sem) │ (4-6 sem) │ (8-12 sem)│ Approval
```

---

## PHASE 1: CRITICAL HARDENING (2-3 Weeks)

**Goal:** Bloqueadores de produção resolvidos
**Team:** 2 devs (full-time)
**Deliverable:** Production-ready security

### Week 1

#### SEC-002: Webhook Signature Validation (CRITICAL)
```
├─ TASK 1.1: Implement HMAC validation
│  ├─ Function: viabixValidateWebhookSignature()
│  ├─ Algorithm: hash_hmac('sha256', $payload, WEBHOOK_SECRET)
│  ├─ Implementation time: 4h
│  └─ Test time: 4h
├─ TASK 1.2: Add signature header check
│  └─ Verify: X-Signature-256 header
├─ TASK 1.3: Add timestamp validation (prevent replay)
│  └─ Allow: ±5 min drift
└─ TASK 1.4: Update webhook_billing.php
   └─ Add validation before processing

Status: 🔴 NOT STARTED
Timeline: Day 1-2
```

#### SEC-001: Persistent Rate Limiting (HIGH)
```
├─ TASK 1.5: Setup Redis (or DB fallback)
│  ├─ Install: Redis 7.x
│  └─ Config: TTL, keys, limits
├─ TASK 1.6: Implement viabixRateLimitRedis()
│  ├─ Key format: rl:action:ip:timestamp
│  ├─ Increment & check atomically
│  └─ Set TTL: 300 seconds (login), 3600 (api)
├─ TASK 1.7: Replace session-based checks
│  └─ All endpoints: login, signup, api_call
└─ TASK 1.8: Add Redis fallback (if Redis down)
   └─ Use database table: rate_limit_log

Status: 🔴 NOT STARTED
Timeline: Day 2-3
```

#### PERF-001: Database Indexes (HIGH)
```
├─ TASK 1.9: Audit missing indexes
│  ├─ Search: WHERE tenant_id = ?
│  └─ Find: 15+ tables without index
├─ TASK 1.10: Create migration SQL
│  ├─ ALTER TABLE usuarios ADD INDEX idx_tenant_id (tenant_id)
│  ├─ ALTER TABLE anvis ADD INDEX idx_tenant_status (tenant_id, status)
│  ├─ ALTER TABLE logs_atividade ADD INDEX idx_tenant_created (tenant_id, created_at)
│  └─ ... (10 more tables)
├─ TASK 1.11: Run migration in staging
│  └─ Verify: query performance improves
└─ TASK 1.12: Schedule for production
   └─ Off-peak: Saturday 2 AM UTC

Status: 🟡 50% (indexes identified, SQL drafted)
Timeline: Day 3
```

### Week 2

#### SEC-003: Tenant Isolation Audit (CRITICAL)
```
├─ TASK 2.1: Grep all SELECT queries
│  └─ Pattern: "SELECT.*FROM" grep result: 150 queries
├─ TASK 2.2: Classify queries
│  ├─ Type A: Has tenant_id filter ✅
│  ├─ Type B: Missing tenant_id filter ❌
│  ├─ Type C: Not applicable (global tables)
│  └─ Count: ~30 Type B queries found
├─ TASK 2.3: Fix Type B queries
│  ├─ anvi.php:40-43 (FIXED: lines 40, 43)
│  ├─ usuarios.php:65 (ADD: AND tenant_id = ?)
│  ├─ logs_atividade (ADD: WHERE tenant_id = ?)
│  └─ ... (15 more locations)
└─ TASK 2.4: Add tenant validation middleware
   ├─ viabixValidateTenantAccess()
   └─ Throw 403 if mismatch

Status: 🟡 STARTED (audit in progress)
Timeline: Day 5-7
```

#### SEC-006: Email Delivery (HIGH)
```
├─ TASK 2.5: Choose provider
│  ├─ Option A: SendGrid (API) ← RECOMMENDED
│  ├─ Option B: SMTP relay
│  └─ Option C: AWS SES
├─ TASK 2.6: Implement SendGrid integration
│  ├─ API key: ENV var MAIL_SENDGRID_API_KEY
│  ├─ Function: viabixSendEmailSendGrid()
│  │  ├─ POST https://api.sendgrid.com/v3/mail/send
│  │  └─ Handle response: 200 = success, 4xx = error
│  ├─ Implement: template rendering
│  └─ Add: error logging to Sentry
├─ TASK 2.7: Create email templates
│  ├─ welcome.html (onboarding)
│  ├─ password_reset.html
│  ├─ invoice.html (billing)
│  └─ 2fa_code.html
├─ TASK 2.8: Test end-to-end
│  ├─ Signup flow: receives welcome email ✓
│  ├─ Password reset: token email ✓
│  └─ Invoice: payment email ✓
└─ TASK 2.9: Deploy to staging
   └─ Verify: all 3 scenarios pass

Status: 🔴 NOT STARTED
Timeline: Day 7-10
```

#### SEC-010: HTTP Security Headers (MEDIUM)
```
├─ TASK 2.10: Create viabixApplySecurityHeaders()
│  ├─ header('X-Frame-Options: DENY')
│  ├─ header('X-Content-Type-Options: nosniff')
│  ├─ header('X-XSS-Protection: 1; mode=block')
│  ├─ header('Referrer-Policy: strict-origin-when-cross-origin')
│  └─ header('Permissions-Policy: microphone=(), camera=(), geolocation=()')
└─ TASK 2.11: Call in api/config.php bootstrap
   └─ All responses get headers

Status: 🔴 NOT STARTED
Timeline: Day 10-11
```

### Week 3

#### SEC-009: 2FA Integration (HIGH)
```
├─ TASK 3.1: Schema updates
│  ├─ ALTER TABLE usuarios ADD COLUMN totp_secret VARCHAR(32)
│  ├─ ALTER TABLE usuarios ADD COLUMN totp_enabled BOOLEAN DEFAULT FALSE
│  ├─ ALTER TABLE usuarios ADD COLUMN backup_codes JSON
│  └─ ALTER TABLE usuarios ADD COLUMN last_2fa_verified DATETIME
├─ TASK 3.2: Modify login.php flow
│  ├─ Step 1: Email/password validation (existing)
│  ├─ Step 2: Check if totp_enabled ← NEW
│  ├─ Step 3: If yes, return 200 with challenge_id ← NEW
│  ├─ Step 4: Frontend shows 6-digit prompt ← NEW
│  └─ Step 5: POST /api/verify_2fa.php with code ← NEW
├─ TASK 3.3: Create verify_2fa.php endpoint
│  ├─ Accept: challenge_id, code (6 digits or backup code)
│  ├─ Verify: viabixTwoFactorAuth->verifyTotp()
│  ├─ On success: Create session (existing logic)
│  └─ Log: audit event
├─ TASK 3.4: Create setup_2fa.php endpoint
│  ├─ Generate QR code
│  ├─ Generate backup codes
│  ├─ Show on setup page
│  └─ Require verification before enabling
└─ TASK 3.5: Test QR code generation
   └─ Use Google Authenticator app

Status: 🔴 NOT STARTED
Timeline: Day 11-14
```

#### SEC-012: Password Reset (HIGH)
```
├─ TASK 3.6: Create forgot_password.php endpoint
│  ├─ Accept: email
│  ├─ Find user by email
│  ├─ Generate token: bin2hex(random_bytes(32))
│  ├─ Store in temp table: password_reset_tokens
│  ├─ Set expiry: 1 hour
│  └─ Send email with reset link
├─ TASK 3.7: Create reset_password.php endpoint
│  ├─ Accept: token, new_password
│  ├─ Validate token (exists, not expired)
│  ├─ Update password (bcrypt)
│  ├─ Delete token
│  ├─ Log audit event
│  └─ Send confirmation email
├─ TASK 3.8: Frontend: add forms
│  ├─ Forgot password modal
│  ├─ Reset password page
│  └─ Success message
└─ TASK 3.9: End-to-end testing
   └─ Full flow: request → email → reset → new login

Status: 🔴 NOT STARTED
Timeline: Day 12-14
```

---

## PHASE 2: QUALITY & FEATURES (4-6 Weeks)

**Goal:** Production-quality code, key missing features
**Team:** 2 devs
**Deliverable:** Test coverage, API robustness, compliance

### Week 4-5: Testing & Type Safety

```
├─ BP-001: Add Type Hints (All Functions)
│  ├─ Files: 50 PHP files
│  ├─ Functions: 250+
│  ├─ Hours: 100h (0.5h per function)
│  └─ QA: Type checking via `php -l` + PHPStan
├─ BP-002: Setup PHPUnit Testing
│  ├─ Install: composer require phpunit/phpunit
│  ├─ Config: phpunit.xml
│  ├─ Base test: Tests/TestCase.php
│  ├─ Auth tests: 15 tests
│  ├─ CSRF tests: 10 tests
│  ├─ Rate limit tests: 10 tests
│  ├─ Validation tests: 20 tests
│  └─ Total: 55 tests (50h)
└─ BP-003: Magic Strings → Enums
   ├─ UserRole enum
   ├─ PlanStatus enum
   ├─ PaymentStatus enum
   ├─ InvoiceStatus enum
   └─ TenantStatus enum (20h)
```

### Week 5-6: Compliance & Features

```
├─ MISSING-004: GDPR Data Export
│  ├─ New endpoint: /api/export_user_data.php
│  ├─ Return: JSON/CSV with all user data
│  ├─ Data: profile, projects, anvis, invoices, logs
│  ├─ Format: JSON + ZIP + CSV options
│  └─ Test: GDPR compliance (40h)
├─ MISSING-005: Webhook Retry
│  ├─ Queue table: webhook_queue
│  ├─ Retry logic: exponential backoff
│  ├─ Schedule: cron job (process every 5 min)
│  ├─ Retries: 1m, 5m, 30m, 2h, 24h
│  └─ Test: simulated failures (40h)
├─ MISSING-006: Plan Enforcement (Backend)
│  ├─ Check: feature access before allow operation
│  ├─ Add: viabixCheckFeatureAccess() middleware
│  ├─ Protect: /api/projetos, /api/anvis export
│  └─ Test: feature gates (30h)
└─ SEC-005: Encryption at Rest
   ├─ Identify: PII columns (email, phone)
   ├─ Functions: viabixEncrypt(), viabixDecrypt()
   ├─ Algorithm: AES-256-GCM
   ├─ Key: ENV var ENCRYPTION_KEY
   └─ Migration: re-encrypt existing data (60h)
```

**Deliverables:**
- ✅ 55+ unit tests
- ✅ Type-safe code (0 PHP errors)
- ✅ GDPR compliant
- ✅ Webhook resilience
- ✅ Data encryption

---

## PHASE 3: REFACTORING & OPTIMIZATION (8-12 Weeks)

**Goal:** Scalable, maintainable architecture
**Team:** 2 devs
**Deliverable:** Clean architecture, high performance

### Week 7-8: Middleware & Refactoring

```
├─ ARCH-002: Middleware Pipeline
│  ├─ Create: Middleware interface
│  ├─ Implement: 
│  │  ├─ CorsMiddleware
│  │  ├─ CsrfMiddleware
│  │  ├─ RateLimitMiddleware
│  │  ├─ AuthMiddleware
│  │  └─ ValidationMiddleware
│  ├─ Pipeline: chain() method
│  └─ Usage: $pipeline->add(...)->handle($request) (50h)
├─ ARCH-001: Split config.php
│  ├─ Database/Connection.php
│  ├─ Database/Schema.php
│  ├─ Auth/AuthService.php
│  ├─ Billing/BillingService.php
│  ├─ Error/ErrorHandler.php
│  └─ config.php: bootstrap only (80h)
└─ ARCH-003: Repository Pattern
   ├─ Repositories/AnviRepository.php
   ├─ Repositories/UserRepository.php
   ├─ Repositories/SubscriptionRepository.php
   ├─ Update: all endpoints to use repos (80h)
   └─ Test: repository layer (40h)
```

### Week 8-10: Performance Optimization

```
├─ PERF-003: Fix N+1 Queries
│  ├─ admin_saas.php: 1 + 100 → 1 query (JOINs)
│  ├─ Queries to optimize: 15+
│  └─ Hours: 30h
├─ PERF-002: Add Pagination
│  ├─ Modify: all GET list endpoints
│  ├─ Signature: ?page=1&limit=50
│  ├─ Default: limit=50, max=200
│  ├─ Response: add total count
│  └─ Hours: 40h
├─ PERF-005: Query Caching (Redis)
│  ├─ Cache: plans, tenants, subscriptions
│  ├─ TTL: 5 minutes
│  ├─ Invalidation: on write
│  ├─ Fallback: DB if Redis down
│  └─ Hours: 30h
└─ PERF-004: JSON Column Optimization
   ├─ Extract: frequently-searched fields
   ├─ Create: generated columns with indexes
   ├─ Example: anvi_numero from JSON_EXTRACT
   └─ Hours: 20h
```

### Week 10-12: CI/CD & Documentation

```
├─ DEVOPS: GitHub Actions CI/CD
│  ├─ Trigger: on push, PR
│  ├─ Steps:
│  │  ├─ Setup PHP 8.2
│  │  ├─ Install dependencies
│  │  ├─ Run PHPUnit tests
│  │  ├─ Run PHPStan (static analysis)
│  │  ├─ Run PHP CodeSniffer (style)
│  │  └─ Deploy to staging (if main branch)
│  └─ Hours: 30h
├─ Documentation
│  ├─ API documentation (OpenAPI/Swagger)
│  ├─ Architecture guide
│  ├─ Deployment runbook
│  ├─ Troubleshooting guide
│  └─ Hours: 40h
└─ Load Testing
   ├─ Tool: Apache Bench or k6
   ├─ Scenarios:
   │  ├─ 100 concurrent users
   │  ├─ 1000 requests/sec
   │  ├─ 10M webhook events
   │  └─ Measure: p99 latency, errors
   └─ Hours: 20h
```

**Deliverables:**
- ✅ Modular architecture
- ✅ Repository pattern
- ✅ Query optimization (10x faster)
- ✅ Automated CI/CD
- ✅ Load test results

---

## Launch Checklist

```
SECURITY ✅
├─ [ ] CSRF on all POST/PUT/DELETE
├─ [ ] Rate limiting (Redis)
├─ [ ] Webhook signatures (HMAC)
├─ [ ] Tenant isolation (100%)
├─ [ ] Input validation (all endpoints)
├─ [ ] HTTP security headers
├─ [ ] SSL/TLS (Let's Encrypt)
├─ [ ] CORS whitelist (no wildcard)
├─ [ ] 2FA enforcement (admins)
└─ [ ] Password hashing (bcrypt cost=12)

PERFORMANCE ✅
├─ [ ] Database indexes (tenant_id, created_at)
├─ [ ] Pagination (all endpoints)
├─ [ ] N+1 queries eliminated
├─ [ ] Response compression (gzip)
├─ [ ] Query caching (Redis)
├─ [ ] Connection pooling
├─ [ ] Slow query logging
├─ [ ] Load test passed (p99 < 500ms)
└─ [ ] Response size < 5MB

OPERATIONS ✅
├─ [ ] Daily backups (30+ day retention)
├─ [ ] Monitoring active (Sentry)
├─ [ ] Log aggregation
├─ [ ] Health check working
├─ [ ] Disaster recovery plan
├─ [ ] On-call rotation setup
├─ [ ] Runbook updated
└─ [ ] Staging environment mirrored

COMPLIANCE ✅
├─ [ ] GDPR: data export endpoint
├─ [ ] GDPR: data deletion endpoint
├─ [ ] PII: encryption at rest
├─ [ ] Audit logs: complete & queryable
├─ [ ] Privacy policy: updated
├─ [ ] Terms of Service: updated
├─ [ ] Data Processing Agreement: signed
└─ [ ] Legal review: passed

QUALITY ✅
├─ [ ] Unit tests: 55+ tests
├─ [ ] Coverage: > 60%
├─ [ ] Type checking: 0 errors
├─ [ ] Code style: PSR-12 compliant
├─ [ ] Security scanning: 0 high issues
├─ [ ] Load test: p99 < 500ms
└─ [ ] User acceptance test: passed
```

---

## Dependencies & Tools

### Software Requirements
```
PHP 8.2+
MySQL 8.0+ or MariaDB 10.11+
Redis 7.x (optional but recommended)
Nginx 1.24+ or Apache 2.4+
Let's Encrypt (SSL)
SendGrid or SMTP server
```

### Development Tools
```
PHPUnit 10+ (testing)
PHPStan (static analysis)
PHP CodeSniffer (code style)
Composer (dependencies)
Git (version control)
GitHub Actions (CI/CD)
```

### Third-Party Services
```
SendGrid (email) - $15-100/mo
Sentry (monitoring) - free-$99/mo
Redis Labs or AWS ElastiCache - $20-100/mo
```

---

## Success Metrics

### Pre-Launch
```
✅ Security Audit: 0 CRITICAL, <3 HIGH issues
✅ Performance: p99 latency < 500ms
✅ Test Coverage: > 60%
✅ Load Test: 1000 req/sec sustained
✅ Uptime: 99.9% in staging
```

### Post-Launch (30-90 days)
```
✅ Uptime: > 99.9%
✅ Error rate: < 0.1%
✅ Avg latency: < 200ms
✅ Security incidents: 0
✅ Customer satisfaction: > 4/5
✅ Data export requests: 100% success
```

---

## Review & Approval Gates

| Phase | Gate | Owner | Approval |
|-------|------|-------|----------|
| Phase 1 | Security review | CISO | Req'd |
| Phase 1-2 | Load test > 1k req/sec | DevOps | Req'd |
| Phase 2 | Code review (all PRs) | TechLead | Req'd |
| Phase 2-3 | Legal/Compliance | Legal | Req'd |
| Phase 3 | UAT test pass | Product | Req'd |
| Launch | Final sign-off | CEO + CISO | Req'd |

---

## Contact & Escalation

| Role | Escalation |
|------|-----------|
| Security Issues | security@viabix.com |
| Performance Issues | devops@viabix.com |
| Architecture Questions | tech-lead@viabix.com |
| Legal/Compliance | legal@viabix.com |

---

**Document Version:** 1.0  
**Last Updated:** 2026-05-03  
**Next Review:** Weekly during development
