# Limiting logins

The primary method of limiting logins should be based on a fast semi-persistent datastore. A key-value store (like most caches) is perfect for this.

## Limiter Types

* MemcacheLoginLimitPlugin: Memcache-based login limiter.
* CookieLoginLimitPlugin: Login limiter based on cookies stored on the user's browser. Always use this in concert with another login limiter.
* HybridLoginLimitPlugin: Combine several login limit plugins into one.

## Why Cookie-Based?

Yeah, you're right. Doing login limiting with cookies makes no sense if a user could in theory clear that cookie... Or does it make sense?

Turns out that limiting logins based on IP addresses has a side effect if some of your users are jerks and share an IP address. Say, a NAT'ed office. They might try usernames and passwords a bunch of times, and lock *everyone* out. To alleviate this, you can add a cookie-based plugin that limits login attempts on a per-browser basis to keep *that guy* from causing problems for the rest of the office.

Cookie-based login limiting, combined with normal server-side limiting is a simple anti-trolling defence.
