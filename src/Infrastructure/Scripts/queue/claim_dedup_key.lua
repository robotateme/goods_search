local key = KEYS[1]
local ttl_seconds = tonumber(ARGV[1])
local value = ARGV[2]

if redis.call('EXISTS', key) == 1 then
    return 0
end

redis.call('SET', key, value, 'EX', ttl_seconds)

return 1
