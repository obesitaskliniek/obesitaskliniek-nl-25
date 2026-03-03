# Implementation Plans

This document provides detailed implementation plans for items tracked in [TODO.md](./TODO.md).

**Last Updated:** 2026-03-03
**Theme:** `nok-2025-v1`

---

## Table of Contents

1. [SECURITY-001: REST API Rate Limiting](#security-001-rest-api-rate-limiting)

---

## SECURITY-001: REST API Rate Limiting

**Status:** Verification Required
**Type:** Infrastructure/DevOps Task
**Risk Level:** HIGH - Public endpoints without rate limiting can enable abuse

### Background

The REST endpoints in `RestEndpoints.php` intentionally delegate rate limiting to server-level configuration (nginx/WAF) rather than implementing application-level limiting. This is architecturally sound but requires verification that server-level protection is in place.

**Public Endpoints (permission_callback: `__return_true`):**

| Endpoint | Method | Risk |
|----------|--------|------|
| `/wp-json/nok-2025-v1/v1/posts/query` | GET | Post enumeration, resource exhaustion |
| `/wp-json/nok-2025-v1/v1/embed-page-part/{id}` | GET | Resource exhaustion via repeated renders |
| `/wp-json/nok-2025-v1/v1/search/autocomplete` | GET | DoS via expensive queries (capped at 20 results) |

**Admin-only endpoints (not at risk):**
- `/nok/v1/page-part/{id}/prune-fields` (POST, `edit_posts`)
- `/nok/v1/page-part/{id}/orphaned-fields` (GET, `edit_posts`)
- `/nok-2025-v1/v1/link-search` (GET, `edit_posts`)

### Existing Application-Level Controls

The endpoints have input validation but no rate limiting:
- **posts/query:** Blocked post-type whitelist (23 internal types), only public types allowed, per_page capped at 50
- **search/autocomplete:** Min 1 char query, limit capped at 20 results
- **embed-page-part:** No input validation beyond WordPress post existence

### Verification Plan

1. **Document current infrastructure**
   - Identify hosting provider
   - Check for existing WAF services (Cloudflare, AWS WAF, Sucuri, Wordfence)
   - Review nginx configuration

2. **Verification test**
   ```bash
   # Test rate limiting (run from external IP)
   for i in {1..50}; do curl -s -o /dev/null -w "%{http_code}\n" \
     "https://www.obesitaskliniek.nl/wp-json/nok-2025-v1/v1/search/autocomplete?q=test"; done
   # Should see 429 responses after threshold
   ```

3. **Expected nginx configuration**
   ```nginx
   # /etc/nginx/conf.d/rate-limit.conf
   limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;

   # In server block or location
   location ~ /wp-json/nok-2025-v1/ {
       limit_req zone=api burst=20 nodelay;
       limit_req_status 429;
   }
   ```

### Fallback: Application-Level Rate Limiting

If server-level rate limiting cannot be confirmed, implement lightweight application-level limiting:

```php
// inc/PageParts/RateLimiter.php (new file)
namespace NOK2025\V1\PageParts;

class RateLimiter {
    private const RATE_LIMIT = 60;        // requests per window
    private const WINDOW_SECONDS = 60;    // 1 minute window

    public static function check(string $endpoint): bool {
        $ip = self::get_client_ip();
        $key = "rate_limit_{$endpoint}_" . md5($ip);

        $current = get_transient($key);
        if ($current === false) {
            set_transient($key, 1, self::WINDOW_SECONDS);
            return true;
        }

        if ($current >= self::RATE_LIMIT) {
            return false; // Rate limited
        }

        set_transient($key, $current + 1, self::WINDOW_SECONDS);
        return true;
    }

    private static function get_client_ip(): string {
        // Standard IP detection with proxy support
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return explode(',', $_SERVER[$header])[0];
            }
        }
        return 'unknown';
    }
}
```

**Files to Modify:**
| File | Change |
|------|--------|
| `inc/PageParts/RestEndpoints.php` | Add rate limit check to public callbacks |
| New: `inc/PageParts/RateLimiter.php` | Lightweight rate limiter class |

**Testing:**
- [ ] Verify server-level rate limiting exists
- [ ] If not, implement and test fallback
- [ ] Load test with 100 concurrent requests
- [ ] Verify legitimate traffic is unaffected

---

## Resolved Plans (removed 2026-03-03)

The following plans were removed because their TODO items are resolved:
- HIGH-001: Cache Invalidation for Page Parts (resolved 2026-01-26)
- HIGH-002: SEO Integration (resolved 2026-01-26)
- HIGH-003: Critical CSS → ATF/BTF Pipeline (resolved 2026-02-23)
- MED-001: tel Field Type (resolved 2026-01-26)
- MED-002: Voorlichtingen Carousel Integration (resolved 2026-01-26)
- MED-003: Usage Tracking System (resolved 2026-02-23)
- DEP-001: Global Helper Functions (resolved 2026-01-26)
- DEP-002: FieldContext Legacy Methods (resolved 2026-01-26)
