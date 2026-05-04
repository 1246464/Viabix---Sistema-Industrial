# 🚀 VIABIX Phase 1: Immediate Actions (This Week)

**Generated:** May 3, 2026  
**Status:** ⚠️ NOT PRODUCTION READY (5.3/10)  
**Phase 1 Duration:** 2-3 weeks  
**Critical Blockers:** 5

---

## 🎯 Top 5 Priorities (Do These FIRST)

### 1️⃣ Webhook Signature Validation (3 days)
**File:** `api/webhook_billing.php`  
**Severity:** 🔴 CRITICAL  
**Risk:** Payment fraud ($1M+)

**What to do:**
```php
// ADD WEBHOOK SIGNATURE VALIDATION

// In api/webhook_billing.php, line 20 (before processing webhook):
function viabixValidateWebhookSignature($payload, $signature) {
    $secret = getenv('WEBHOOK_SECRET');
    $calculated = hash_hmac('sha256', $payload, $secret);
    return hash_equals($calculated, $signature);
}

// Update viabixApplyBillingEvent() to validate first:
if (!viabixValidateWebhookSignature($raw_payload, $_SERVER['HTTP_X_ASAAS_SIGNATURE'])) {
    http_response_code(401);
    die('Invalid signature');
}
```

**Checklist:**
- [ ] Add WEBHOOK_SECRET to .env.production
- [ ] Implement viabixValidateWebhookSignature()
- [ ] Call validation before processing webhook
- [ ] Test with Asaas test webhooks
- [ ] Deploy to production

**Estimated Time:** 16 hours

---

### 2️⃣ Persistent Rate Limiting with Redis (1 week)
**File:** `api/rate_limit.php`  
**Severity:** 🟠 HIGH  
**Risk:** Brute force attacks

**What to do:**
```php
// REPLACE SESSION-BASED RATE LIMITING WITH REDIS

// In api/config.php, add Redis connection pool:
$redis = new Redis();
$redis->connect(getenv('REDIS_HOST', 'localhost'), getenv('REDIS_PORT', 6379));

// Update api/rate_limit.php:
function viabixCheckIpRateLimit($ip, $limit = 100, $window = 3600) {
    global $redis;
    $key = "rate_limit:{$ip}";
    
    $count = $redis->incr($key);
    if ($count == 1) {
        $redis->expire($key, $window);
    }
    
    if ($count > $limit) {
        http_response_code(429);
        die('Too many requests');
    }
    return true;
}
```

**Checklist:**
- [ ] Install redis-server (or use DigitalOcean Redis)
- [ ] Add Redis connection to config.php
- [ ] Refactor viabixCheckIpRateLimit() to use Redis
- [ ] Add REDIS_HOST, REDIS_PORT to .env
- [ ] Test rate limiting with concurrent requests
- [ ] Deploy Redis connection to production

**Estimated Time:** 40 hours

---

### 3️⃣ Email Delivery (SendGrid) (2 weeks)
**File:** `api/email.php`  
**Severity:** 🟠 HIGH  
**Risk:** Onboarding broken, password recovery impossible

**What to do:**
```php
// IMPLEMENT SENDGRID EMAIL DELIVERY

// In api/email.php, replace stub with:
<?php
require 'vendor/autoload.php';

use SendGrid\Mail\Mail;

function viabixSendEmail($to, $subject, $htmlContent) {
    $from = getenv('EMAIL_FROM', 'noreply@viabix.com');
    $sendGridKey = getenv('SENDGRID_API_KEY');
    
    $email = new Mail();
    $email->setFrom($from);
    $email->setSubject($subject);
    $email->addTo($to);
    $email->addContent("text/html", $htmlContent);
    
    $sendgrid = new \SendGrid($sendGridKey);
    try {
        $response = $sendgrid->send($email);
        return $response->statusCode() == 202;
    } catch (Exception $e) {
        error_log("Email send failed: " . $e->getMessage());
        return false;
    }
}

// Usage examples:
// viabixSendEmail('user@example.com', 'Welcome to VIABIX', $htmlTemplate);
```

**Checklist:**
- [ ] Create SendGrid account (free tier available)
- [ ] Generate API key
- [ ] Add SENDGRID_API_KEY to .env
- [ ] Install SendGrid PHP library: `composer require sendgrid/sendgrid-php`
- [ ] Implement viabixSendEmail() in api/email.php
- [ ] Create email templates (welcome, password reset, 2FA codes)
- [ ] Update signup.php to send welcome email
- [ ] Update password reset flow to send reset link
- [ ] Test email sending in staging
- [ ] Deploy to production

**Estimated Time:** 40 hours

---

### 4️⃣ Database Indexes on tenant_id (1 day)
**File:** `BD/migracao_para_saas_fase1_v2.sql`  
**Severity:** 🟠 HIGH  
**Risk:** Query performance degradation

**What to do:**
```sql
-- Add indexes to all tenant tables
-- Run in production database:

ALTER TABLE usuarios ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE anvis ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE anvis ADD INDEX idx_tenant_slug (tenant_id, slug);
ALTER TABLE logs_atividade ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE logs_atividade ADD INDEX idx_tenant_usuario (tenant_id, usuario_id);
ALTER TABLE conflitos_edicao ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE notificacoes ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE invoices ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE invoices ADD INDEX idx_tenant_status (tenant_id, status);
ALTER TABLE payments ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE subscriptions ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE webhook_events ADD INDEX idx_tenant_id (tenant_id);

-- Verify indexes created:
SHOW INDEX FROM usuarios WHERE Key_name = 'idx_tenant_id';
```

**Checklist:**
- [ ] Backup database before adding indexes
- [ ] Run SQL statements in order
- [ ] Verify each index with SHOW INDEX
- [ ] Test query performance (should be 10x faster)
- [ ] Deploy to production

**Estimated Time:** 4 hours

---

### 5️⃣ Tenant Isolation Audit & Enforcement (2 weeks)
**File:** `api/anvi.php`, `api/usuarios.php`, and 15+ other files  
**Severity:** 🔴 CRITICAL  
**Risk:** Data leakage between customers

**What to do:**
```php
// AUDIT: Check that ALL queries filter by tenant_id

// Example fix in api/anvi.php:
// BEFORE (insecure):
$sql = "SELECT * FROM anvis WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

// AFTER (secure):
function viabixGetAnvi($id) {
    global $pdo;
    $tenant_id = $_SESSION['tenant_id'] ?? null;
    if (!$tenant_id) {
        throw new Exception("No tenant context");
    }
    
    $sql = "SELECT * FROM anvis WHERE id = ? AND tenant_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $tenant_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Create middleware to enforce tenant context:
function viabixRequireTenantContext() {
    if (empty($_SESSION['tenant_id'])) {
        http_response_code(403);
        die('No tenant context');
    }
}
```

**Checklist:**
- [ ] Grep for all SELECT queries without tenant_id: `grep -r "SELECT.*FROM" api/ --include="*.php"`
- [ ] Audit 20+ locations with tenant_id checks missing
- [ ] Fix each query to include `AND tenant_id = ?`
- [ ] Add middleware call to all endpoints
- [ ] Test cross-tenant data access (should fail)
- [ ] Deploy to production

**Estimated Time:** 40 hours

---

## 📅 Week 1 Schedule

### Day 1 (Monday)
- [ ] 09:00 - Team sync: Review analysis, assign tasks
- [ ] 10:00 - Setup: Create .env.production with all secrets
- [ ] 11:00 - START: Webhook signature validation
- [ ] 14:00 - START: Redis setup & connection pooling

### Day 2 (Tuesday)
- [ ] Continue webhook validation (code + tests)
- [ ] Continue Redis rate limiting refactoring
- [ ] REQUEST: SendGrid API key from platform

### Day 3 (Wednesday)
- [ ] COMPLETE: Webhook validation (submit PR for review)
- [ ] COMPLETE: Rate limiting (submit PR for review)
- [ ] START: Database index SQL script
- [ ] Code review + testing

### Day 4 (Thursday)
- [ ] COMPLETE: Database indexes (deploy to staging)
- [ ] Test performance improvements
- [ ] START: Tenant isolation audit
- [ ] Prepare SendGrid templates

### Day 5 (Friday)
- [ ] Continue tenant isolation fixes (20+ locations)
- [ ] QA: Verify webhook validation works
- [ ] QA: Verify rate limiting works
- [ ] Weekly standup: Report Phase 1 progress

---

## 🛠️ Phase 1 Full Checklist

### Week 1: Critical Security (5 tasks)
- [ ] Webhook signature validation (DONE)
- [ ] Rate limiting Redis (DONE)
- [ ] Database indexes (DONE)
- [ ] Tenant isolation audit (DONE)
- [ ] HTTP security headers (START)

### Week 2: Email & Headers (4 tasks)
- [ ] Email delivery SendGrid (DONE)
- [ ] Password reset endpoint (DONE)
- [ ] 2FA framework integration (START)
- [ ] Security headers (X-Frame-Options, CSP, HSTS)

### Week 3: 2FA & Testing (3 tasks)
- [ ] 2FA in login flow (DONE)
- [ ] Full integration testing
- [ ] Performance validation
- [ ] READY: Phase 1 complete

---

## 🔐 Security Gate (After Phase 1)

**Approval Criteria:**
- ✓ Webhook validation implemented & tested
- ✓ Rate limiting persistent & working
- ✓ Tenant isolation 100% enforced
- ✓ Email delivery functional
- ✓ Database indexes deployed
- ✓ Security audit: 0 CRITICAL, <3 HIGH

**Who Approves:** CISO + Tech Lead

**Next:** Phase 2 kickoff (Quality & Features)

---

## 📊 Effort Breakdown (Week 1-3)

| Task | Days | Hours | Developer |
|------|------|-------|-----------|
| Webhook validation | 2-3 | 16 | Dev 1 |
| Redis rate limiting | 5 | 40 | Dev 1 |
| Email SendGrid | 10 | 40 | Dev 2 |
| Database indexes | 1 | 4 | Dev 1 |
| Tenant isolation | 10 | 40 | Dev 2 |
| HTTP headers | 2 | 8 | Dev 1 |
| Password reset | 5 | 20 | Dev 2 |
| 2FA integration | 10 | 40 | Dev 1 |
| Testing & QA | 5 | 20 | Both |
| **PHASE 1 TOTAL** | **15 days** | **150h** | **Both** |

---

## 🚨 Risk Mitigation

| Risk | Mitigation | Owner |
|------|-----------|-------|
| Webhook validation not complete | Start TODAY, 3-day deadline | Dev 1 |
| Rate limiting still broken | Redis deployment in week 1 | Dev 1 |
| Email not implemented | SendGrid integration in week 2 | Dev 2 |
| Tenant data leaks | Audit all queries, complete week 2 | Dev 2 |
| Database locks on indexes | Do during low-traffic window | DBA |

---

## ✅ Success Metrics

### Week 1 Goal
- [ ] Webhook validation: 0 false negatives
- [ ] Rate limiting: <100ms overhead
- [ ] Database indexes: 10x query speedup

### Week 2 Goal
- [ ] Email: 99% delivery success
- [ ] Tenant isolation: 100% enforced
- [ ] Password reset: Working end-to-end

### Week 3 Goal
- [ ] 2FA: Integrated into login
- [ ] All Phase 1 items complete
- [ ] Zero new critical vulnerabilities

---

## 🎯 Next Week Planning (Now)

**Send this to your team:**

> **VIABIX Phase 1 Starts This Week**
>
> We have 5 critical blockers to fix in 2-3 weeks:
>
> 1. Webhook signature validation (3 days)
> 2. Persistent rate limiting (1 week)
> 3. Email delivery (2 weeks)
> 4. Database indexes (1 day)
> 5. Tenant isolation (2 weeks)
>
> **This week we tackle items 1-4.**
>
> **Team:**
> - Dev 1: Webhook + Rate limiting + Security headers
> - Dev 2: Email + Tenant isolation audit
>
> **Kickoff:** Monday 09:00
>
> See PROJECT_ANALYSIS.json for full technical details.

---

## 📞 Questions?

**For Phase 1 details:** See [PRODUCTION_ROADMAP.md](PRODUCTION_ROADMAP.md)  
**For all issues:** See [PROJECT_ANALYSIS.json](PROJECT_ANALYSIS.json)  
**For quick ref:** See [QUICK_REFERENCE.md](QUICK_REFERENCE.md)  

---

**Phase 1 Start:** THIS WEEK ✅  
**Phase 1 End:** Week 3 (Apr 24)  
**Status:** Ready to implement  
**Owner:** Development Team
