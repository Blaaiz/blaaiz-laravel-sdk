# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

The Blaaiz team takes security bugs seriously. We appreciate your efforts to responsibly disclose your findings, and will make every effort to acknowledge your contributions.

### How to Report

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to: **security@blaaiz.com**

Include the following information:
- Type of issue (e.g. buffer overflow, SQL injection, cross-site scripting, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit the issue

### What to Expect

- **Acknowledgment**: We will acknowledge receipt of your vulnerability report within 48 hours
- **Initial Assessment**: We will provide an initial assessment within 72 hours
- **Status Updates**: We will send status updates at least every 72 hours until resolution
- **Resolution Timeline**: We aim to resolve critical vulnerabilities within 7 days, high severity within 14 days

### Disclosure Policy

- We request that you do not disclose the vulnerability publicly until we have had a chance to address it
- We will work with you to determine an appropriate disclosure timeline
- We will credit you for your discovery in our security advisory (unless you prefer to remain anonymous)

### Security Best Practices for Users

When using the Blaaiz Laravel SDK:

1. **Keep Dependencies Updated**
   ```bash
   composer update blaaiz/laravel-sdk
   ```

2. **Secure API Key Storage**
   - Store API keys in environment variables, not in code
   - Use Laravel's encryption for sensitive configuration
   - Rotate API keys regularly

3. **Validate Input Data**
   ```php
   // Always validate user input before passing to SDK
   $validator = Validator::make($request->all(), [
       'amount' => 'required|numeric|min:0',
       'currency' => 'required|string|size:3'
   ]);
   ```

4. **Use HTTPS**
   - Always use HTTPS in production
   - The SDK enforces HTTPS for API communications

5. **Error Handling**
   ```php
   try {
       $result = $blaaiz->payouts->initiate($data);
   } catch (BlaaizException $e) {
       // Handle errors properly - don't expose sensitive details
       Log::error('Payout failed', ['error' => $e->getMessage()]);
       return response()->json(['error' => 'Transaction failed'], 500);
   }
   ```

6. **Logging Security**
   - Never log API keys or sensitive customer data
   - Use Laravel's logging with appropriate levels
   - Monitor for suspicious activity

### Security Features

The SDK includes several security features:

- **Request Signing**: All API requests are properly signed
- **Webhook Verification**: HMAC signature verification for webhooks
- **Input Validation**: Built-in validation for required fields
- **Secure Defaults**: HTTPS enforcement, secure timeouts
- **Exception Handling**: Proper error handling without sensitive data exposure

### Vulnerability Disclosure Timeline

1. **Day 0**: Vulnerability reported
2. **Day 1-2**: Acknowledgment sent
3. **Day 3**: Initial assessment and severity rating
4. **Day 7-14**: Fix developed and tested (depending on severity)
5. **Day 14-21**: Fix deployed and security advisory published
6. **Day 21+**: Public disclosure (coordinated with reporter)

### Security Updates

Security updates will be published as:
- **Critical**: Emergency patch release
- **High**: Next patch release (within 14 days)
- **Medium/Low**: Next minor release

### Contact

For security-related questions or concerns:
- Email: security@blaaiz.com
- For general questions: support@blaaiz.com

Thank you for helping keep Blaaiz and our users safe!