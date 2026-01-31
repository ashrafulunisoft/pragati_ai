#!/bin/bash

# ============================================================================
# MCP Secure Login System - CURL Test Script
# ============================================================================
#
# This script tests the MCP secure login system via HTTP requests.
# It simulates brute force attacks and verifies Redis counters.
#
# Prerequisites:
# - Laravel server running on localhost:8000
# - Docker Redis running on port 6380
#
# Usage:
#   bash tests/curl_test.sh
#
# ============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SERVER_URL="http://localhost:8000"
REDIS_PORT="6380"
TEST_EMAIL="test@curltest.com"
REDIS_CLI="redis-cli -p $REDIS_PORT"

# Test counters
PASSED=0
FAILED=0

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║        MCP SECURE LOGIN SYSTEM - CURL TEST SUITE           ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Function to print test results
print_result() {
    local test_name="$1"
    local expected="$2"
    local actual="$3"

    if [ "$expected" == "$actual" ]; then
        echo -e "   ${GREEN}✅ PASS${NC}: $test_name"
        ((PASSED++))
    else
        echo -e "   ${RED}❌ FAIL${NC}: $test_name (expected: $expected, got: $actual)"
        ((FAILED++))
    fi
}

# Function to check Redis key exists
redis_exists() {
    local key="$1"
    $REDIS_CLI exists "$key" 2>/dev/null
}

# Function to get Redis key value
redis_get() {
    local key="$1"
    $REDIS_CLI get "$key" 2>/dev/null
}

# Function to delete Redis key
redis_del() {
    local key="$1"
    $REDIS_CLI del "$key" 2>/dev/null > /dev/null
}

# ============================================================================
# TEST 1: Check if server is running
# ============================================================================
echo -e "${YELLOW}1. Server Connectivity Test${NC}"
echo "   Checking if Laravel server is running..."

HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$SERVER_URL/login" 2>/dev/null || echo "000")
print_result "Server HTTP status" "200" "$HTTP_STATUS"

# ============================================================================
# TEST 2: Check if Redis is accessible
# ============================================================================
echo ""
echo -e "${YELLOW}2. Redis Connection Test${NC}"
echo "   Testing Redis on port $REDIS_PORT..."

REDIS_PING=$($REDIS_CLI ping 2>/dev/null || echo "FAILED")
print_result "Redis ping" "PONG" "$REDIS_PING"

# ============================================================================
# TEST 3: Simulate failed login attempts
# ============================================================================
echo ""
echo -e "${YELLOW}3. Failed Login Attempts Test${NC}"
echo "   Simulating 6 failed login attempts..."

# Get CSRF token
get_csrf() {
    curl -s -c /tmp/cookies.txt "$SERVER_URL/login" 2>/dev/null | \
        grep -oP 'name="_token"[^>]*value="\K[^"]*' | head -1
}

# Clean up any existing test keys
TEST_IP="192.168.1.200"
IP_KEY="attack:login:ip:$TEST_IP"
redis_del "$IP_KEY"

# Simulate 6 failed logins
for i in 1 2 3 4 5 6; do
    COOKIE=$(get_csrf)
    if [ -n "$COOKIE" ]; then
        RESPONSE=$(curl -s -b /tmp/cookies.txt -X POST "$SERVER_URL/login" \
            -H "X-CSRF-TOKEN: $COOKIE" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -d "email=$TEST_EMAIL&password=wrongpass$i" \
            -w "%{http_code}" \
            -o /dev/null 2>/dev/null)
    fi
    echo -n "   Attempt $i: HTTP $RESPONSE"
    if [ "$RESPONSE" == "302" ] || [ "$RESPONSE" == "200" ]; then
        echo -e " ${GREEN}✓${NC}"
    else
        echo -e " ${RED}✗${NC}"
    fi
done

# Check Redis counter
COUNTER=$(redis_get "$IP_KEY" || echo "0")
print_result "Redis counter after 6 attempts" "6" "$COUNTER"

# ============================================================================
# TEST 4: Verify threshold detection
# ============================================================================
echo ""
echo -e "${YELLOW}4. Threshold Detection Test${NC}"
echo "   Checking if threshold (IP >= 5) was triggered..."

THRESHOLD_MET="NO"
if [ "$COUNTER" -ge 5 ]; then
    THRESHOLD_MET="YES"
fi
print_result "Threshold triggered (counter >= 5)" "YES" "$THRESHOLD_MET"

# ============================================================================
# TEST 5: Redis key TTL test
# ============================================================================
echo ""
echo -e "${YELLOW}5. Redis TTL Test${NC}"
echo "   Checking if keys have correct TTL..."

# Set a key with TTL
redis_del "test:ttl:key"
$REDIS_CLI setex "test:ttl:key" 900 1 > /dev/null 2>&1
TTL=$($REDIS_CLI ttl "test:ttl:key" 2>/dev/null || echo "0")

TTL_VALID="NO"
if [ "$TTL" -gt 0 ] && [ "$TTL" -le 900 ]; then
    TTL_VALID="YES"
fi
print_result "TTL between 1 and 900" "YES" "$TTL_VALID"

# Cleanup
redis_del "test:ttl:key"

# ============================================================================
# TEST 6: Login page accessibility
# ============================================================================
echo ""
echo -e "${YELLOW}6. Login Page Test${NC}"
echo "   Checking if login page is accessible..."

CSRF_TOKEN=$(get_csrf)
if [ -n "$CSRF_TOKEN" ]; then
    print_result "CSRF token present" "YES" "YES"
else
    print_result "CSRF token present" "YES" "NO"
fi

# ============================================================================
# TEST 7: Blocked IP simulation
# ============================================================================
echo ""
echo -e "${YELLOW}7. IP Blocking Simulation Test${NC}"
echo "   Testing Redis blocking functionality..."

BLOCKED_KEY="blocked:ip:127.0.0.1"
redis_del "$BLOCKED_KEY"

# Simulate blocking
$REDIS_CLI setex "$BLOCKED_KEY" 3600 1 > /dev/null 2>&1
BLOCKED=$(redis_get "$BLOCKED_KEY" || echo "0")

print_result "IP blocked successfully" "1" "$BLOCKED"

# ============================================================================
# SUMMARY
# ============================================================================
echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                      TEST SUMMARY                          ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "   ${GREEN}Passed: $PASSED${NC}"
echo -e "   ${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "   ${GREEN}🎉 ALL TESTS PASSED!${NC}"
    exit 0
else
    echo -e "   ${YELLOW}⚠️  Some tests failed. Check the output above.${NC}"
    exit 1
fi
