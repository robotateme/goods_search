import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  scenarios: {
    browse_catalog: {
      executor: 'ramping-vus',
      startVUs: 1,
      stages: [
        { duration: '30s', target: 10 },
        { duration: '1m', target: 25 },
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '10s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500'],
  },
};

const baseUrl = __ENV.BASE_URL || 'http://localhost/api/products';
const scenarios = [
  '?page=1&per_page=20',
  '?sort=price_asc&page=1&per_page=20',
  '?sort=rating_desc&page=2&per_page=20',
  '?category_id=1&in_stock=true&page=1&per_page=20',
  '?price_from=50&price_to=250&rating_from=4&page=1&per_page=20',
  '?q=Mouse&page=1&per_page=20',
  '?q=Keyboard&sort=price_desc&page=1&per_page=20',
];

export default function () {
  const query = scenarios[Math.floor(Math.random() * scenarios.length)];
  const response = http.get(`${baseUrl}${query}`, {
    headers: {
      Accept: 'application/json',
    },
  });

  check(response, {
    'status is 200': (r) => r.status === 200,
    'has data array': (r) => Array.isArray(r.json('data')),
    'has pagination fields': (r) => r.json('current_page') !== null && r.json('per_page') !== null,
  });

  sleep(1);
}
