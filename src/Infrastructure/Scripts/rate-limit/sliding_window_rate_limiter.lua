local key = KEYS[1]
local now_ms = tonumber(ARGV[1])
local window_ms = tonumber(ARGV[2])
local limit = tonumber(ARGV[3])
local member = ARGV[4]

redis.call('ZREMRANGEBYSCORE', key, '-inf', now_ms - window_ms)

local current_count = redis.call('ZCARD', key)

if current_count >= limit then
    local oldest = redis.call('ZRANGE', key, 0, 0, 'WITHSCORES')
    local retry_after_ms = window_ms

    if oldest[2] ~= nil then
        retry_after_ms = math.max(0, tonumber(oldest[2]) + window_ms - now_ms)
    end

    return {0, current_count, retry_after_ms}
end

redis.call('ZADD', key, now_ms, member)
redis.call('PEXPIRE', key, window_ms)

local remaining = limit - (current_count + 1)

return {1, remaining, 0}
